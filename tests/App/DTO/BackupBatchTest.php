<?php

namespace Tests\App\DTO;

use App\DTO\BackupBatch;
use PHPUnit\Framework\TestCase;

class BackupBatchTest extends TestCase
{
    public function testIterator()
    {
        $batch = new BackupBatch([
            ['from' => 'fromA', 'to' => 'toA', 'configName' => 'configName'],
            ['from' => 'fromB', 'to' => 'toB', 'configName' => 'configName'],
        ]);

        self::assertTrue($batch->valid());
        self::assertEquals(0, $batch->key());
        self::assertEquals('fromA', $batch->current()->from);

        $batch->next();

        self::assertTrue($batch->valid());
        self::assertEquals(1, $batch->key());
        self::assertEquals('fromB', $batch->current()->from);

        $batch->next();

        self::assertFalse($batch->valid());

        $batch->rewind();

        self::assertTrue($batch->valid());
        self::assertEquals(0, $batch->key());
        self::assertEquals('fromA', $batch->current()->from);
    }
}
