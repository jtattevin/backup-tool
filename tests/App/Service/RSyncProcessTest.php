<?php

namespace Tests\App\Service;

use App\DTO\BackupFolder;
use App\Service\ProcessRunner;
use App\Service\RSyncProcess;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class RSyncProcessTest extends TestCase
{
    public static function dataProviderTestExecute(): \Generator
    {
        yield [
            'from' => dirname(__DIR__, 3).'/example/from',
            'to' => dirname(__DIR__, 3).'/example/to',
        ];
        yield [
            'from' => __DIR__.'/../../../example/from',
            'to' => __DIR__.'/../../../example/to',
        ];
    }

    #[DataProvider('dataProviderTestExecute')]
    public function testExecute(string $from, string $to): void
    {
        $processRunner = $this->createMock(ProcessRunner::class);
        $processRunner
            ->expects($this->once())
            ->method('startProcess')
            ->willReturnCallback(function (array|string $script, string $outputPath, string $workdir, OutputStyle $output, bool $dryRun) use ($from, $to) {
                self::assertEquals([
                    'rsync',
                    '--archive',
                    '--verbose',
                    '--files-from='.dirname(__DIR__, 3).'/example/from/.backups/included.txt',
                    $from,
                    $to,
                ], $script);
                self::assertEquals('', $outputPath);
                self::assertEquals(__DIR__, $workdir);
                self::assertTrue($dryRun);

                return $this->createMock(Process::class);
            })
        ;
        $rsyncProcess = new RSyncProcess($processRunner);

        $backup = new BackupFolder([
            'from' => $from,
            'to' => $to,
            'configName' => 'backup.yml',
        ]);
        $backup->configure(['dump_scripts' => [], 'ignore_pattern' => [], 'ignore_folder' => [], 'before_backup' => null, 'during_backup' => null, 'after_backup' => null]);

        $rsyncProcess->execute($backup, __DIR__, $this->createMock(OutputStyle::class), true);
    }

    #[DataProvider('dataProviderTestExecute')]
    public function testBuildFileList(string $from, string $to): void
    {
        $processRunner = $this->createMock(ProcessRunner::class);
        $rsyncProcess = new RSyncProcess($processRunner);

        $backup = new BackupFolder([
            'from' => $from,
            'to' => $to,
            'configName' => 'backup.yml',
        ]);
        $backup->configure([
            'dump_scripts' => [],
            'ignore_pattern' => [
                'file2',
                "#^dir1/.ile3\.txt$#",
                "#^dir1/file4\.tx$#",
            ],
            'ignore_folder' => [
                'dir2',
            ],
            'before_backup' => null,
            'during_backup' => null,
            'after_backup' => null,
        ]);
        new Filesystem()->mkdir($backup->workDirPath);

        $rsyncProcess->execute($backup, __DIR__, $this->createMock(OutputStyle::class), true);

        self::assertEqualsCanonicalizing([
            '.backups/output-script-A',
            'file1.txt',
            'dir1/dir2/file6.txt',
            'dir1/dir2/file8.txt',
            'dir1/file4.txt',
        ], explode("\n", file_get_contents($backup->includedListPath)));

        self::assertStringContainsString(
            <<<EXCLUDED
            ### Folder dir2
            ### Pattern file2
            file2.txt
            
            ### Pattern #^dir1/.ile3\.txt$#
            dir1/file3.txt
            
            ### Pattern #^dir1/file4\.tx$#
            
            ### Pattern #^\.backups/(log|excluded|included|summary)\.txt$#
            EXCLUDED,
            file_get_contents($backup->excludedListPath)
        );
    }
}
