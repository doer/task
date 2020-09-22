<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Xiaoe\Task\Event;
use Xiaoe\Task\Message\Ipc;
use Xiaoe\Task\Scheduler\DelayTaskScheduler;
use Xiaoe\Task\Scheduler\NormalTaskScheduler;
use Xiaoe\Task\Server;

require __DIR__ . '/../vendor/autoload.php';

$message = new Ipc();

$event = new Event(
    [
        new NormalTaskScheduler($message),
        new DelayTaskScheduler($message),
    ]
);

$logger = new Logger('start');
$logger->pushHandler(new StreamHandler(__DIR__ . '/run'));
// $logger->pushHandler(new StreamHandler(STDOUT));

$options = [
    'server' => [
        'lock_dir' => __DIR__,
    ]
];

(new Server($event, $logger, $options))->start($_SERVER['argv'][1]);

