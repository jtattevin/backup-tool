<?php

namespace Tests\App\Console\Output;

use App\Console\Output\DuplicateOutput;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class DuplicateOutputTest extends TestCase
{
    public function testDuplicateOutput()
    {
        $outputA = new BufferedOutput();
        $outputB = new BufferedOutput();

        $duplicateOutput = new DuplicateOutput($outputA, $outputB);
        $duplicateOutput->write('message');

        self::assertEquals('message', $outputA->fetch());
        self::assertEquals('message', $outputB->fetch());
    }

    #[TestWith([OutputInterface::VERBOSITY_DEBUG, OutputInterface::VERBOSITY_DEBUG, OutputInterface::VERBOSITY_DEBUG])]
    #[TestWith([OutputInterface::VERBOSITY_DEBUG, OutputInterface::VERBOSITY_QUIET, OutputInterface::VERBOSITY_DEBUG])]
    #[TestWith([OutputInterface::VERBOSITY_QUIET, OutputInterface::VERBOSITY_DEBUG, OutputInterface::VERBOSITY_QUIET])]
    #[TestWith([OutputInterface::VERBOSITY_QUIET, OutputInterface::VERBOSITY_QUIET, OutputInterface::VERBOSITY_QUIET])]
    public function testVerbosityOutput(int $verbosityA, int $verbosityB, int $expectedVerbosity): void
    {
        $outputA = new BufferedOutput($verbosityA);
        $outputB = new BufferedOutput($verbosityB);

        $duplicateOutput = new DuplicateOutput($outputA, $outputB);

        self::assertEquals($expectedVerbosity, $duplicateOutput->getVerbosity());
    }

    #[TestWith([true, true, true])]
    #[TestWith([true, false, true])]
    #[TestWith([false, true, false])]
    #[TestWith([false, false, false])]
    public function testDecorationOutput(bool $decorationA, bool $decorationB, bool $expectedDecoration): void
    {
        $outputA = new BufferedOutput(decorated: $decorationA);
        $outputB = new BufferedOutput(decorated: $decorationB);

        $duplicateOutput = new DuplicateOutput($outputA, $outputB);

        self::assertEquals($expectedDecoration, $duplicateOutput->isDecorated());
    }
}
