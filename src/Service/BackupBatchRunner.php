<?php

namespace App\Service;

use App\Config\BackupConfiguration;
use App\DTO\BackupBatch;
use App\DTO\BackupFolder;
use DuplicateOutput;
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

class BackupBatchRunner
{
    public function executeBatch(BackupBatch $backupBatch, OutputStyle $output): bool
    {
        $success = true;
        foreach ($backupBatch as $backup) {
            $success = $this->executeBackup($backup, $output) && $success;
        }

        return $success;
    }

    private function executeBackup(BackupFolder $backup, OutputStyle $output): bool
    {
        try {

            $output->section("Begin backup of " . $backup->from . " -> " . $backup->to . " using " . $backup->configName);
            $this->readConfig($backup);
            $this->ensureWorkDirExist($backup);

            $output = $this->swapOutput($output, $backup);
            $root   = new TreeNode($backup->from);

            if ($backup->dumpScripts) {
                $output->title("Running dump scripts");
                $messages = $this->runDumpScript($backup, $output);
                $root->addChild($node = new TreeNode("Dump scripts"));
                foreach ($messages as $scriptName => $message) {
                    $node->addChild(new TreeNode($scriptName . " : " . $message));
                }
            }

            if ($backup->beforeBackup) {
                $output->title("Running before backup scripts");
                $beforeMessage = $this->runBeforeBackupScript($backup, $output);
                $root->addChild(new TreeNode("Before : " . $beforeMessage));
            }

            $duringBackup = null;
            if ($backup->duringBackup) {
                $output->title("Start during backup scripts");
                $duringBackup = $this->startDuringBackupScript($backup, $output);
            }

            // Backup here

            if ($duringBackup !== null) {
                $output->title("Stop during backup scripts");
                $duringMessage = $this->stopDuringBackupScript($duringBackup, $output);
                $root->addChild(new TreeNode("During : ".$duringMessage));
            }

            if ($backup->afterBackup) {
                $output->title("Running after backup scripts");
                $afterMessage = $this->runAfterBackupScript($backup, $output);
                $root->addChild(new TreeNode("After : ".$afterMessage));
            }

            $tree = TreeHelper::createTree($output, $root);
            $tree->render();


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

    /** @noinspection PhpVoidFunctionResultUsedInspection */
    private function startProcess(string $script, string $outputPath, OutputStyle $output): Process
    {
        $errorOutput = method_exists($output, "getErrorStyle") ? $output->getErrorStyle() : $output;

        $process = Process::fromShellCommandline($script);
        $process->setEnv(["OUTPUT_PATH" => $outputPath]);
        $process->start(static fn ($type, $data) => match ($type) {
            Process::OUT => $output->write($data),
            default      => $errorOutput->write($data),
        });

        return $process;
    }

    private function waitProcess(Process $process, ?string $scriptName, OutputStyle $output): string
    {
        $exitCode = $process->wait();
        $duration = microtime(true) - $process->getStartTime();

        if ($scriptName !== null) {
            $message = sprintf("End script %s, exit code : %d, duration : %d", $scriptName, $exitCode, round($duration, 3));
        } else {
            $message = sprintf("Exit code : %d, duration : %d", $exitCode, round($duration, 3));
        }

        if ($exitCode === 0) {
            $output->success($message);
            return "🟢 ".$message;
        }

        $output->warning($message);
        return "🟠 ".$message;

    }

    private function runDumpScript(BackupFolder $backup, OutputStyle $output): array
    {
        $messages = [];
        foreach ($backup->dumpScripts as $scriptName => $script) {
            $output->note("Begin script " . $scriptName);

            $process               = $this->startProcess(
                $script,
                $backup->workDirPath . "/output-" . $scriptName,
                $output
            );
            $messages[$scriptName] = $this->waitProcess($process, $scriptName, $output);
        }

        return $messages;
    }

    private function runBeforeBackupScript(BackupFolder $backup, OutputStyle $output): string
    {
        $process = $this->startProcess($backup->beforeBackup, $backup->beforeBackupLogPath, $output);

        return $this->waitProcess($process, null, $output);
    }

    private function startDuringBackupScript(BackupFolder $backup, OutputStyle $output): Process
    {
        $process = $this->startProcess($backup->duringBackup, $backup->duringBackupLogPath, $output);

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

        return $this->waitProcess($duringBackup, null, $output);
    }

    private function runAfterBackupScript(BackupFolder $backup, OutputStyle $output): string
    {
        $process = $this->startProcess($backup->afterBackup, $backup->afterBackupLogPath, $output);

        return $this->waitProcess($process, null, $output);
    }
}
