<?php

namespace Tests\App\Service;

use App\Service\ProcessRunner;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class ProcessRunnerTest extends TestCase
{
    #[TestWith([['echo', 'Hello, world !'], false, "Hello, world !\n"])]
    #[TestWith([['echo', 'Hello, world !'], true, "echo Hello, world !\n"])]
    #[TestWith(['echo "Hello, world !"', false, "Hello, world !\n"])]
    #[TestWith(['echo "Hello, world !"', true, "echo \"Hello, world !\"\n"])]
    #[TestWith(['pwd', false, "/tmp\n"])]
    #[TestWith(['echo $OUTPUT_PATH', false, "/tmp/output-path\n"])]
    public function testStartProcess(array|string $script, bool $dryRun, string $expectedOutput): void
    {
        $output = new BufferedOutput();
        $processRunner = new ProcessRunner();
        $process = $processRunner->startProcess(
            $script,
            '/tmp/output-path',
            '/tmp',
            new SymfonyStyle(new ArrayInput([]), $output),
            $dryRun
        );
        $process->wait();

        self::assertEquals(null, $process->getTimeout());
        self::assertEquals($expectedOutput, $output->fetch());
    }

    #[TestWith(['true', 'scriptName', '🟢 End script scriptName, exit code : 0, duration : 0.0', '[OK] End script scriptName, exit code : 0, duration : 0.0'])]
    #[TestWith(['false', 'scriptName', '🟠 End script scriptName, exit code : 1, duration : 0.0', '[WARNING] End script scriptName, exit code : 1, duration : 0.0'])]
    #[TestWith(['true', null, '🟢 Exit code : 0, duration : 0.0', '[OK] Exit code : 0, duration : 0.0'])]
    #[TestWith(['false', null, '🟠 Exit code : 1, duration : 0.0', '[WARNING] Exit code : 1, duration : 0.0'])]
    public function testWaitProcess(string $command, ?string $scriptName, string $expectedMessage, string $expectedOutput): void
    {
        $output = new BufferedOutput();

        $process = Process::fromShellCommandline($command);
        $process->start();

        $processRunner = new ProcessRunner();
        $message = $processRunner->waitProcess(
            $process,
            $scriptName,
            new SymfonyStyle(new ArrayInput([]), $output)
        );

        self::assertStringContainsString($expectedMessage, $message);
        self::assertStringContainsString($expectedOutput, $output->fetch());
    }
}
