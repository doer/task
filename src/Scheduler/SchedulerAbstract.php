<?php

namespace Xiaoe\Task\Scheduler;

use Xiaoe\Task\Message\MessageInterface;
use Xiaoe\Task\Payload\Scheduler as SchedulerPayload;
use Xiaoe\Task\Process\PoolInterface;

abstract class SchedulerAbstract implements SchedulerInterface
{
    /**
     * @var PoolInterface
     */
    protected $processPool;

    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     * @param MessageInterface
     */
    public function __construct(MessageInterface $message)
    {
        $this->message = $message;
    }

    /**
     * {@inheritDoc}
     */
    public function setProcessPool(PoolInterface $pool)
    {
        $this->processPool = $pool;
    }

    /**
     * 将任务发送至调度队列，等待 Worker 处理
     */
    protected function sendJobToWorker($params)
    {
        $payload = (new SchedulerPayload)->valid($params)->toArray();
        $this->message->sendToWorker($payload);
    }
}
