<?php

namespace Tests\App;

use App\Kernel;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    public function testGetProjectDir()
    {
        $kernel = new Kernel('test', true);

        $root = realpath(dirname(__DIR__, 2));
        $this->assertEquals($root, realpath($kernel->getProjectDir()));
    }
}
