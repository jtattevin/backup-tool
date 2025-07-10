<?php

namespace App\DTO;

use Iterator;
use RuntimeException;

/** @implements Iterator<int,BackupFolder> */
class BackupBatch implements Iterator
{
    /** @var array<int,BackupFolder> */
    private(set) array $folders;

    /**
     * @param array<int,array{from:string,to:string,configName:string}> $config
     */
    public function __construct(array $config)
    {
        $this->folders = array_map(
            fn ($config) => new BackupFolder($config),
            $config
        );
    }

    public function current(): mixed
    {
        return current($this->folders) ?: throw new RuntimeException("Can't read past end of batch");
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
