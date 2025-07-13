<?php

namespace Tests\App\Command;

use App\Command\RunBackup;
use App\Service\BackupBatchRunner;
use App\DTO\BackupBatch;
use App\Validator\ConfigFilenameValidator;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class RunBackupTest extends TestCase
{
    #[TestWith([true, 0])]
    #[TestWith([false, 1])]
    public function testRunBackup(bool $executeReturn, int $expectedExitCode): void
    {
        $output = new BufferedOutput();
        $input = new ArrayInput([
            'configPath' => dirname(__DIR__, 3).'/example/main.yml',
        ]);

        $configFilenameValidator = $this->createMock(ConfigFilenameValidator::class);
        $configFilenameValidator->expects($this->once())->method('validate');

        $backupBatchRunner = $this->createMock(BackupBatchRunner::class);
        $backupBatchRunner->expects($this->once())->method('executeBatch')->willReturn($executeReturn);

        $command = new RunBackup();
        $command->configFilenameValidator = $configFilenameValidator;
        $command->backupBatchRunner = $backupBatchRunner;
        $exitCode = $command->run($input, $output);

        self::assertEmpty($output->fetch());
        self::assertEquals($expectedExitCode, $exitCode);
    }


    #[TestWith(["from", 1])]
    #[TestWith(["missingFolder", 0])]
    public function testRunFilter(string $filter, int $expectedRun): void
    {
        $output = new NullOutput();
        $input = new ArrayInput([
            'configPath' => dirname(__DIR__, 3).'/example/main.yml',
            '--filter' => $filter
        ]);

        $configFilenameValidator = $this->createMock(ConfigFilenameValidator::class);
        $configFilenameValidator->expects($this->once())->method('validate');

        $backupBatchRunner = $this->createMock(BackupBatchRunner::class);
        $backupBatchRunner
            ->expects($this->once())
            ->method('executeBatch')
            ->willReturnCallback(function(BackupBatch $backupBatch) use ($expectedRun) {
                self::assertEquals($expectedRun, iterator_count($backupBatch));
                return true;
            });

        $command = new RunBackup();
        $command->configFilenameValidator = $configFilenameValidator;
        $command->backupBatchRunner = $backupBatchRunner;
        $exitCode = $command->run($input, $output);



    }

    public function testRunBackupValidationError()
    {
        $output = new BufferedOutput();
        $input = new ArrayInput([
            'configPath' => dirname(__DIR__, 3).'/example/main.yml',
        ]);

        $configFilenameValidator = $this->createMock(ConfigFilenameValidator::class);
        $configFilenameValidator->expects($this->once())
            ->method('validate')
            ->willThrowException($this->createMock(ValidationFailedException::class));

        $backupBatchRunner = $this->createMock(BackupBatchRunner::class);
        $backupBatchRunner->expects($this->never())->method('executeBatch');

        $command = new RunBackup();
        $command->configFilenameValidator = $configFilenameValidator;
        $command->backupBatchRunner = $backupBatchRunner;
        $exitCode = $command->run($input, $output);

        self::assertStringContainsString('[ERROR]', $output->fetch());
        self::assertEquals(1, $exitCode);
    }
}
