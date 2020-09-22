<?php

namespace Xiaoe\Task\Scheduler;

use Throwable;
use Xiaoe\Task\Exception\Exception;
use Xiaoe\Task\Payload\Exchange as ExchangePayload;

/**
 * 进程调度器
 *
 */
class ProcessScheduler extends SchedulerAbstract
{
    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        /**
         * 进程调度器不需要使用进程池进行作业，统一返回 0
         * 每次事件循环处理所有进程通信消息
         */

        // 回收子进程资源
        $this->processPool->recovery();

        try {
            while (true) {
                $params = $this->message->receiveByExchange(false);
                if (! $params) {
                    return 0;
                }

                $payload = (new ExchangePayload)->valid($params);
                // 更新进程池对应进程状态
                $this->processPool->setWorkerStatus(
                    $payload->getPid(),
                    $payload->getStatus()
                );
            }
        } catch (Throwable $e) {
            Exception::handle($e);
        }

        return 0;
    }
}

