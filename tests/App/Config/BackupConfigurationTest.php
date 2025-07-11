<?php

namespace Tests\App\Config;

use App\Config\BackupConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class BackupConfigurationTest extends TestCase
{
    public function testMinimalConfig()
    {
        $processedConfiguration = new Processor()->processConfiguration(
            new BackupConfiguration(),
            [
                [],
            ]
        );

        self::assertEquals([
            'dump_scripts' => [],
            'ignore_pattern' => [],
            'ignore_folder' => [],
            'before_backup' => null,
            'during_backup' => null,
            'after_backup' => null,
        ], $processedConfiguration);
    }

    public function testFullConfig()
    {
        $processedConfiguration = new Processor()->processConfiguration(
            new BackupConfiguration(),
            [
                [
                    'dump_scripts' => [
                        'scriptAName' => 'scriptAValue',
                    ],
                    'ignore_pattern' => [
                        'ignorePatternA',
                    ],
                    'ignore_folder' => [
                        'ignoreFolderA',
                    ],
                    'before_backup' => 'beforeBackupScript',
                    'during_backup' => 'duringBackupScript',
                    'after_backup' => 'afterBackupScript',
                ],
            ]
        );

        self::assertEquals([
            'dump_scripts' => [
                'scriptAName' => 'scriptAValue',
            ],
            'ignore_pattern' => [
                'ignorePatternA',
            ],
            'ignore_folder' => [
                'ignoreFolderA',
            ],
            'before_backup' => 'beforeBackupScript',
            'during_backup' => 'duringBackupScript',
            'after_backup' => 'afterBackupScript',
        ], $processedConfiguration);
    }
}
