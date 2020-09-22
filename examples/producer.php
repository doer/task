<?php

use Xiaoe\Task\Message\Ipc;
use Xiaoe\Task\Producer;
use Xiaoe\Task\Test\DelayTaskUnitTest;
use Xiaoe\Task\Test\NormalTaskUnitTest;

require __DIR__ . '/../vendor/autoload.php';

$producer = new Producer(new Ipc);

for ($i = 0; $i < 100; $i++) {
    $producer->task(
        NormalTaskUnitTest::class,
        $i
    );
}

for ($i = 0; $i < 100; $i++) {
    $producer->delayTask(
        5,
        DelayTaskUnitTest::class,
        $i
    );
    sleep(1);
}
