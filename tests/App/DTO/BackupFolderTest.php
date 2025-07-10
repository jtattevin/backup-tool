<?php

namespace Tests\App\DTO;

use App\DTO\BackupFolder;
use PHPUnit\Framework\TestCase;

class BackupFolderTest extends TestCase
{
    public function testConstructor()
    {
        $backupFolder = new BackupFolder([
            'from' => '/tmp/../tmp',
            'to' => 'toValue',
            'configName' => 'configNameValue',
        ]);

        $this->assertSame('/tmp/../tmp', $backupFolder->from);
        $this->assertSame('toValue', $backupFolder->to);
        $this->assertSame('configNameValue', $backupFolder->configName);
        $this->assertSame('/tmp/.backups', $backupFolder->workDirPath);
        $this->assertSame('/tmp/.backups/log.txt', $backupFolder->logPath);
        $this->assertSame('/tmp/.backups/before-backup-script', $backupFolder->beforeBackupLogPath);
        $this->assertSame('/tmp/.backups/during-backup-script', $backupFolder->duringBackupLogPath);
        $this->assertSame('/tmp/.backups/after-backup-script', $backupFolder->afterBackupLogPath);
        $this->assertSame('/tmp/.backups/included.txt', $backupFolder->includedListPath);
        $this->assertSame('/tmp/.backups/excluded.txt', $backupFolder->excludedListPath);
        $this->assertSame('/tmp/.backups/summary.txt', $backupFolder->summaryLogPath);
    }

    public function testConfigure()
    {
        $backupFolder = new BackupFolder([
            'from' => '/tmp',
            'to' => 'toValue',
            'configName' => 'configNameValue',
        ]);
        $backupFolder->configure([
            'dump_scripts' => ['scriptAName' => 'scriptAValue', 'scriptBName' => 'scriptBValue'],
            'ignore_pattern' => ['pattern1', 'pattern2'],
            'ignore_folder' => ['ignoreFolderA', 'ignoreFolderB'],
            'before_backup' => 'beforeBackup',
            'during_backup' => 'duringBackup',
            'after_backup' => 'afterBackup',
        ]);
        self::assertEquals(['scriptAName' => 'scriptAValue', 'scriptBName' => 'scriptBValue'], $backupFolder->dumpScripts);
        self::assertEquals(['pattern1', 'pattern2'], $backupFolder->ignorePattern);
        self::assertEquals(['ignoreFolderA', 'ignoreFolderB'], $backupFolder->ignoreFolder);
        self::assertEquals('beforeBackup', $backupFolder->beforeBackup);
        self::assertEquals('duringBackup', $backupFolder->duringBackup);
        self::assertEquals('afterBackup', $backupFolder->afterBackup);
    }
}
