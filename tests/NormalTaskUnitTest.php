<?php

namespace Xiaoe\Task\Test;

use Xiaoe\Task\UnitInterface;

class NormalTaskUnitTest implements UnitInterface
{
    public function handle($params)
    {
        $pid = posix_getpid();
        file_put_contents(__DIR__ . '/normal_task', "{$pid} $params" . PHP_EOL, 8);
        sleep(5);
    }
}
