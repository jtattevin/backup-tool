<?php

namespace Tests\App\Service;

use App\DTO\BackupBatch;
use App\DTO\BackupFolder;
use App\Service\BackupBatchRunner;
use App\Service\ProcessRunner;
use App\Service\RSyncProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class BackupBatchRunnerTest extends TestCase
{
    public function testRun()
    {
        Clock::set(new MockClock('2025-07-11 12:00:00'));
        $rsync = $this->createMock(RSyncProcess::class);
        $rsync->method('execute')->willReturnCallback(fn (BackupFolder $folder) => $folder->from.'->'.$folder->to);
        $processRunner = $this->createMock(ProcessRunner::class);

        $processRunner
            ->method('startProcess')
            ->willReturnCallback(static function (array|string $script, string $outputPath, string $workdir, OutputStyle $output, bool $dryRun): Process {
                self::assertTrue($dryRun);

                $process = Process::fromShellCommandline(is_array($script) ? implode(' ', $script) : $script);
                $process->setWorkingDirectory($workdir);
                $process->setEnv(['OUTPUT_PATH' => $outputPath]);

                return $process;
            })
        ;
        $processRunner
            ->method('waitProcess')
            ->willReturnCallback(static function (Process $process): string {
                return $process->getCommandLine().' / '.$process->getWorkingDirectory().' / '.$process->getEnv()['OUTPUT_PATH'];
            })
        ;

        $output = new BufferedOutput();
        $wrapperOutput = new SymfonyStyle(new ArrayInput([]), $output);

        $backup = new BackupBatchRunner($rsync, $processRunner);

        $batch = new BackupBatch([
            ['from' => __DIR__.'/../../../example/from', 'to' => __DIR__.'/../../../example/to/dir1', 'configName' => 'backup.yml'],
            ['from' => dirname(__DIR__, 3).'/example/from', 'to' => __DIR__.'/../../../example/to/dir2', 'configName' => 'backup-complete.yml'],
        ]);
        $result = $backup->executeBatch($batch, __DIR__, $wrapperOutput, true);
        self::assertTrue($result);

        $batch = new BackupBatch([
            ['from' => __DIR__.'/../../../example/from', 'to' => __DIR__.'/../../../example/to/dir1', 'configName' => 'backup.yml'],
            ['from' => dirname(__DIR__, 3).'/example/from', 'to' => __DIR__.'/../../../example/to/dir2', 'configName' => 'backup-missing.yml'],
        ]);
        $result = $backup->executeBatch($batch, __DIR__, $wrapperOutput, true);
        self::assertFalse($result);

        $output = str_replace(dirname(__DIR__, 3), '{ROOTDIR}', $output->fetch());
        $output = explode("\n", $output);
        $output = array_map(trim(...), $output);
        $output = implode("\n", $output);

        self::assertEquals(
            file_get_contents(__FILE__, offset: __COMPILER_HALT_OFFSET__),
            $output
        );
    }
}

__halt_compiler();
Begin backup of {ROOTDIR}/tests/App/Service/../../../example/from -> {ROOTDIR}/tests/App/Service/../../../example/to/dir1 using backup.yml
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

! [NOTE] Starting to log in
!        {ROOTDIR}/example/from/.backups/log.txt

{ROOTDIR}/tests/App/Service/../../../example/from
├── Date start : 2025-07-11 12:00:00
├── No dump scripts
├── No before backup scripts
├── RSync : {ROOTDIR}/tests/App/Service/../../../example/from->{ROOTDIR}/tests/App/Service/../../../example/to/dir1
├── No during backup scripts
├── No after backup scripts
└── Date end : 2025-07-11 12:00:00

Begin backup of {ROOTDIR}/example/from -> {ROOTDIR}/tests/App/Service/../../../example/to/dir2 using backup-complete.yml
------------------------------------------------------------------------------------------------------------------------------------------------------------

! [NOTE] Starting to log in
!        {ROOTDIR}/example/from/.backups/log.txt


Running dump scripts
====================

! [NOTE] Begin script echo1

! [NOTE] Begin script echo2

Running before backup scripts
=============================

Start during backup scripts
===========================

[WARNING] Process is not started

Stop during backup scripts
==========================

Running after backup scripts
============================

{ROOTDIR}/example/from
├── Date start : 2025-07-11 12:00:00
├── Dump scripts
│   ├── echo1 : echo 1 / {ROOTDIR}/example/from / {ROOTDIR}/example/from/.backups/output-echo1
│   └── echo2 : echo 2 / {ROOTDIR}/example/from / {ROOTDIR}/example/from/.backups/output-echo2
├── Before : echo before_backup / {ROOTDIR}/example/from / {ROOTDIR}/example/from/.backups/before-backup-script
├── RSync : {ROOTDIR}/example/from->{ROOTDIR}/tests/App/Service/../../../example/to/dir2
├── During : echo during_backup / {ROOTDIR}/example/from / {ROOTDIR}/example/from/.backups/during-backup-script
├── After : echo after_backup / {ROOTDIR}/example/from / {ROOTDIR}/example/from/.backups/after-backup-script
└── Date end : 2025-07-11 12:00:00

Begin backup of {ROOTDIR}/tests/App/Service/../../../example/from -> {ROOTDIR}/tests/App/Service/../../../example/to/dir1 using backup.yml
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

! [NOTE] Starting to log in
!        {ROOTDIR}/example/from/.backups/log.txt

{ROOTDIR}/tests/App/Service/../../../example/from
├── Date start : 2025-07-11 12:00:00
├── No dump scripts
├── No before backup scripts
├── RSync : {ROOTDIR}/tests/App/Service/../../../example/from->{ROOTDIR}/tests/App/Service/../../../example/to/dir1
├── No during backup scripts
├── No after backup scripts
└── Date end : 2025-07-11 12:00:00

Begin backup of {ROOTDIR}/example/from -> {ROOTDIR}/tests/App/Service/../../../example/to/dir2 using backup-missing.yml
-----------------------------------------------------------------------------------------------------------------------------------------------------------

[ERROR] File "{ROOTDIR}/example/from/backup-missing.yml" does
not exist.

