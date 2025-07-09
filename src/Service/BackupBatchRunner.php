<?php

namespace App\Service;

use App\Config\BackupConfiguration;
use App\DTO\BackupBatch;
use App\DTO\BackupFolder;
use App\Console\Output\DuplicateOutput;
use RuntimeException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Helper\TreeHelper;
use Symfony\Component\Console\Helper\TreeNode;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

readonly class BackupBatchRunner
{
    public function __construct(
        private RSyncProcess $rsyncProcess,
        private ProcessRunner $processRunner,
    ) {
    }

    public function executeBatch(BackupBatch $backupBatch, OutputStyle $output, bool $dryRun): bool
    {
        $success = true;
        foreach ($backupBatch as $backup) {
            $success = $this->executeBackup($backup, $output, $dryRun) && $success;
        }

        return $success;
    }

    private function executeBackup(BackupFolder $backup, OutputStyle $output, bool $dryRun): bool
    {
        try {
            $output->section("Begin backup of " . $backup->from . " -> " . $backup->to . " using " . $backup->configName);
            $this->readConfig($backup);
            $this->ensureWorkDirExist($backup);

            $output = $this->swapOutput($output, $backup);
            $root   = new TreeNode($backup->from);
            $root->addChild(new TreeNode("Date start : " . date("Y-m-d H:i:s")));

            if ($backup->dumpScripts) {
                $output->title("Running dump scripts");
                $messages = $this->runDumpScript($backup, $output, $dryRun);
                $root->addChild($node = new TreeNode("Dump scripts"));
                foreach ($messages as $scriptName => $message) {
                    $node->addChild(new TreeNode($scriptName . " : " . $message));
                }
            } else {
                $root->addChild(new TreeNode("No dump scripts"));
            }

            if ($backup->beforeBackup) {
                $output->title("Running before backup scripts");
                $beforeMessage = $this->runBeforeBackupScript($backup, $output, $dryRun);
                $root->addChild(new TreeNode("Before : " . $beforeMessage));
            } else {
                $root->addChild(new TreeNode("No before backup scripts"));
            }

            $duringBackup = null;
            if ($backup->duringBackup) {
                $output->title("Start during backup scripts");
                $duringBackup = $this->startDuringBackupScript($backup, $output, $dryRun);
            }

            // Backup here
            $message = $this->rsyncProcess->execute($backup, $output, $dryRun);
            $root->addChild(new TreeNode("RSync : " . $message));

            if ($duringBackup !== null) {
                $output->title("Stop during backup scripts");
                $duringMessage = $this->stopDuringBackupScript($duringBackup, $output);
                $root->addChild(new TreeNode("During : " . $duringMessage));
            } else {
                $root->addChild(new TreeNode("No during backup scripts"));
            }

            if ($backup->afterBackup) {
                $output->title("Running after backup scripts");
                $afterMessage = $this->runAfterBackupScript($backup, $output, $dryRun);
                $root->addChild(new TreeNode("After : " . $afterMessage));
            } else {
                $root->addChild(new TreeNode("No after backup scripts"));
            }

            $root->addChild(new TreeNode("Date end : " . date("Y-m-d H:i:s")));

            TreeHelper::createTree($output, $root)->render();
            $summaryOutput = new StreamOutput(fopen($backup->summaryLogPath, "w"));
            TreeHelper::createTree($summaryOutput, $root)->render();

        } catch (InvalidConfigurationException|RuntimeException $exception) {
            $output->error($exception->getMessage());

            return false;
        }

        return true;
    }

    private function readConfig(BackupFolder $backup): void
    {
        $processedConfiguration = new Processor()->processConfiguration(
            new BackupConfiguration(),
            [
                Yaml::parseFile($backup->from . "/" . $backup->configName) ?? [],
            ]
        );

        $backup->configure($processedConfiguration);
    }

    private function ensureWorkDirExist(BackupFolder $backup): void
    {
        $fs = new Filesystem;
        $fs->mkdir($backup->workDirPath);
    }

    private function swapOutput(OutputStyle $output, BackupFolder $backup): OutputStyle
    {
        $output->note("Starting to log in " . $backup->logPath);

        return new SymfonyStyle(
            new ArrayInput([]),
            new DuplicateOutput(
                $output,
                new StreamOutput(fopen($backup->logPath, "w"))
            )
        );
    }


    private function runDumpScript(BackupFolder $backup, OutputStyle $output, bool $dryRun): array
    {
        $messages = [];
        foreach ($backup->dumpScripts as $scriptName => $script) {
            $output->note("Begin script " . $scriptName);

            $process               = $this->processRunner->startProcess(
                $script,
                $backup->workDirPath . "/output-" . $scriptName,
                $output,
                $dryRun
            );
            $messages[$scriptName] = $this->processRunner->waitProcess($process, $scriptName, $output);
        }

        return $messages;
    }

    private function runBeforeBackupScript(BackupFolder $backup, OutputStyle $output, bool $dryRun): string
    {
        $process = $this->processRunner->startProcess($backup->beforeBackup, $backup->beforeBackupLogPath, $output, $dryRun);

        return $this->processRunner->waitProcess($process, null, $output);
    }

    private function startDuringBackupScript(BackupFolder $backup, OutputStyle $output, bool $dryRun): Process
    {
        $process = $this->processRunner->startProcess($backup->duringBackup, $backup->duringBackupLogPath, $output, $dryRun);

        if ($process->isStarted()) {
            $output->success("Process is started");
        } else {
            $output->warning("Process is not started");
        }

        return $process;
    }

    private function stopDuringBackupScript(Process $duringBackup, OutputStyle $output): string
    {
        if ($duringBackup->isRunning()) {
            $duringBackup->stop();
        }

        return $this->processRunner->waitProcess($duringBackup, null, $output);
    }

    private function runAfterBackupScript(BackupFolder $backup, OutputStyle $output, bool $dryRun): string
    {
        $process = $this->processRunner->startProcess($backup->afterBackup, $backup->afterBackupLogPath, $output, $dryRun);

        return $this->processRunner->waitProcess($process, null, $output);
    }
}
