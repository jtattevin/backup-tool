<?php

namespace Tests\App\Config;

use App\Config\MainConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class MainConfigurationTest extends TestCase
{
    public function testMinimalConfig()
    {
        $processedConfiguration = new Processor()->processConfiguration(
            new MainConfiguration(__DIR__),
            [
                [
                    ['from' => '/tmp', 'to' => '/tmp'],
                ],
            ]
        );

        self::assertEquals([
            ['from' => '/tmp', 'to' => '/tmp', 'configName' => 'backup.yml'],
        ], $processedConfiguration);
    }

    public function testCompleteConfig()
    {
        $processedConfiguration = new Processor()->processConfiguration(
            new MainConfiguration(__DIR__),
            [
                [
                    ['from' => '/tmp', 'to' => '/tmp', 'configName' => 'backup.yaml'],
                    ['from' => '/tmp', 'to' => 'non-existing', 'configName' => 'backup2.yml'],
                ],
            ]
        );

        self::assertEquals([
            ['from' => '/tmp', 'to' => '/tmp', 'configName' => 'backup.yaml'],
            ['from' => '/tmp', 'to' => 'non-existing', 'configName' => 'backup2.yml'],
        ], $processedConfiguration);
    }

    public function testFromShouldExistConfig()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "backup.0.from": From must be a directory');

        new Processor()->processConfiguration(
            new MainConfiguration(__DIR__),
            [
                [
                    ['from' => '/non-existing', 'to' => '/tmp'],
                ],
            ]
        );
    }

    public function testConfigShouldNotBeEmpty()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "backup" should have at least 1 element(s) defined.');

        new Processor()->processConfiguration(
            new MainConfiguration(__DIR__),
            [
            ]
        );
    }
}
