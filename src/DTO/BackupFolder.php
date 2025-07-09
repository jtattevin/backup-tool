<?php

namespace App\DTO;

class BackupFolder
{
    private(set) string $from;

    private(set) string $to;

    private(set) string $configName;

    private(set) array $dumpScripts;

    private(set) array $ignore;

    private(set) ?string $beforeBackup;

    private(set) ?string $duringBackup;

    private(set) ?string $afterBackup;

    public string $workDirPath {
        get => $this->from . "/.backups";
    }

    public string $logPath {
        get => $this->workDirPath . "/log.txt";
    }
    public string $beforeBackupLogPath {
        get => $this->workDirPath . "/before-backup-script";
    }
    public string $duringBackupLogPath {
        get => $this->workDirPath . "/during-backup-script";
    }
    public string $afterBackupLogPath {
        get => $this->workDirPath . "/after-backup-script";
    }


    public function __construct(
        array $config
    ) {
        $this->from       = $config['from'];
        $this->to         = $config['to'];
        $this->configName = $config['configName'];
    }

    public function configure(array $processedConfiguration): void
    {
        $this->dumpScripts  = $processedConfiguration['dump_scripts'];
        $this->ignore       = $processedConfiguration['ignore'];
        $this->beforeBackup = $processedConfiguration['before_backup'];
        $this->duringBackup = $processedConfiguration['during_backup'];
        $this->afterBackup  = $processedConfiguration['after_backup'];
    }
}
