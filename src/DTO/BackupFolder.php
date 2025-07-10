<?php

namespace App\DTO;

class BackupFolder
{
    public private(set) string $from;

    public private(set) string $to;

    public private(set) string $configName;

    public private(set) array $dumpScripts;

    public private(set) array $ignorePattern;

    public private(set) array $ignoreFolder;

    public private(set) ?string $beforeBackup;

    public private(set) ?string $duringBackup;

    public private(set) ?string $afterBackup;

    public string $workDirPath {
        get => realpath($this->from).'/.backups';
    }

    public string $logPath {
        get => $this->workDirPath.'/log.txt';
    }

    public string $beforeBackupLogPath {
        get => $this->workDirPath.'/before-backup-script';
    }

    public string $duringBackupLogPath {
        get => $this->workDirPath.'/during-backup-script';
    }

    public string $afterBackupLogPath {
        get => $this->workDirPath.'/after-backup-script';
    }

    public string $includedListPath {
        get => $this->workDirPath.'/included.txt';
    }

    public string $excludedListPath {
        get => $this->workDirPath.'/excluded.txt';
    }

    public string $summaryLogPath {
        get => $this->workDirPath.'/summary.txt';
    }

    public function __construct(
        array $config,
    ) {
        $this->from = $config['from'];
        $this->to = $config['to'];
        $this->configName = $config['configName'];
    }

    public function configure(array $processedConfiguration): void
    {
        $this->dumpScripts = $processedConfiguration['dump_scripts'];
        $this->ignorePattern = $processedConfiguration['ignore_pattern'];
        $this->ignoreFolder = $processedConfiguration['ignore_folder'];
        $this->beforeBackup = $processedConfiguration['before_backup'];
        $this->duringBackup = $processedConfiguration['during_backup'];
        $this->afterBackup = $processedConfiguration['after_backup'];
    }
}
