<?php

use Xiaoe\Task\Message\Ipc;
use Xiaoe\Task\Message\MessageInterface;

require __DIR__ . '/../vendor/autoload.php';

$ipc = new Ipc();
var_dump($ipc->getKey());
var_dump($ipc->count());

$res = $ipc->receive(MessageInterface::TYPE_EXCHANGE);
var_dump($res);
