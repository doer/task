<?php

namespace Xiaoe\Task\Scheduler;

use Throwable;
use Xiaoe\Task\Exception\Exception;
use Xiaoe\Task\Payload\NormalTask as NormalTaskPayload;

/**
 * 普通异步任务调度器
 *
 * 调度流程：
 *  1.检查进程池是否有空闲进程
 *  2.如果有空闲进程，从普通异步队列获取一个任务投递至调度队列
 *  2.如果没有空闲进程，等待下一次事件循环重复上面流程
 */
class NormalTaskScheduler extends SchedulerAbstract
{
    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        try {
            $available = $this->processPool->available();
            // 返回 1 表示正常情况下无论是否有作业需求
            // 进程池都需要至少一个空闲进程
            if (! $available) {
                logger()->info('NormalTaskScheduler: 没有可用进程');
                return 1;
            }

            $params = $this->message->receiveByNormalTask(false);
            if (! $params) {
                return 0;
            }

            // 将普通异步任务转投至调度任务队列（等待 Worker 处理）
            // 普通异步任务
            $payloadData = (new NormalTaskPayload)->valid($params)->toArray();
            $this->sendJobToWorker($payloadData);

            logger()->info('NormalTaskScheduler: 任务进入调度队列', $payloadData);

            return 1;

        } catch (Throwable $e) {
            Exception::handle($e);
        }
    }
}

