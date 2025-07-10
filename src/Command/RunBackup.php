<?php

namespace App\Command;

use App\Config\MainConfiguration;
use App\DTO\BackupBatch;
use App\Service\BackupBatchRunner;
use App\Validator\ConfigFilenameValidator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Service\Attribute\Required;

#[AsCommand('backup')]
class RunBackup extends Command
{
    #[Required]
    public ConfigFilenameValidator $configFilenameValidator;

    #[Required]
    public BackupBatchRunner $backupBatchRunner;

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('configPath', InputArgument::REQUIRED);
        $this->addOption('dry-run', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $configPath = $input->getArgument('configPath');
            $dryRun = $input->getOption('dry-run');

            $this->configFilenameValidator->validate($configPath);

            $processedConfiguration = new Processor()->processConfiguration(
                new MainConfiguration(),
                [
                    Yaml::parseFile($configPath)['backup'] ?? [],
                ]
            );

            $backupBatch = new BackupBatch($processedConfiguration);
            if (!$this->backupBatchRunner->executeBatch($backupBatch, $io, $dryRun)) {
                return self::FAILURE;
            }
        } catch (InvalidConfigurationException|ValidationFailedException $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
