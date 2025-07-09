<?php

namespace App\Command;

use App\Config\MainConfiguration;
use App\OptionsResolver\ConfigReader;
use App\Validator\ConfigFilenameValidator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Service\Attribute\Required;

#[AsCommand("backup")]
class RunBackup extends Command
{
    #[Required]
    public ConfigFilenameValidator $configFilenameValidator;

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument("configPath", InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $configPath = $input->getArgument("configPath");

        try {
            $this->configFilenameValidator->validate($configPath);
        } catch (ValidationFailedException $validationFailedException) {
            $io->error($validationFailedException->getMessage());

            return self::FAILURE;
        }

        try {
            $processedConfiguration = new Processor()->processConfiguration(
                new MainConfiguration(),
                [
                    Yaml::parseFile($configPath)["backup"] ?? [],
                ]
            );
        } catch (InvalidConfigurationException $invalidConfigurationException) {
            $io->error($invalidConfigurationException->getMessage());

            return self::FAILURE;
        }

        dump($processedConfiguration);

        return self::SUCCESS;
    }
}
