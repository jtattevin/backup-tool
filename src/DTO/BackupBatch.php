<?php

namespace App\DTO;

class BackupBatch implements \Iterator
{
    public private(set) array $folders;

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

    public function key(): ?int
    {
        return key($this->folders);
    }

    public function valid(): bool
    {
        return null !== key($this->folders);
    }

    public function rewind(): void
    {
        reset($this->folders);
    }
}
