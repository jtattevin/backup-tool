<?php

use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class DuplicateOutput extends Output
{
    public function __construct(
        private readonly OutputInterface $outputA,
        private readonly OutputInterface $outputB
    ) {
        parent::__construct(
            $this->outputA->getVerbosity(),
            $this->outputA->isDecorated(),
        );
    }

    protected function doWrite(string $message, bool $newline): void
    {
        $this->outputA->write($message, $newline);
        $this->outputB->write($message, $newline);
    }


}
