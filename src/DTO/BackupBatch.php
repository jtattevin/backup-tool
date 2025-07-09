<?php

namespace App\DTO;

use Iterator;

class BackupBatch implements Iterator
{
    private(set) array $folders;

    public function __construct(array $config)
    {
        $this->folders = array_map(
            fn ($config) => new BackupFolder($config),
            $config
        );
    }

    public function current(): mixed
    {
        return current($this->folders);
    }

    public function next(): void
    {
        next($this->folders);
    }

    public function key(): int|null
    {
        return key($this->folders);
    }

    public function valid(): bool
    {
        return key($this->folders) !== null;
    }

    public function rewind(): void
    {
        reset($this->folders);
    }
}
