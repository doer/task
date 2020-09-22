<?php

namespace Xiaoe\Task\Scheduler;

use Throwable;
use Xiaoe\Task\Exception\Exception;
use Xiaoe\Task\Payload\DelayTask as DelayTaskPayload;

/**
 * 延时异步任务调度
 *
 * 调度流程：
 */
class DelayTaskScheduler extends SchedulerAbstract
{
    // 内部最大等待任务数
    // 延时任务取出后会内部进行时间排序，等待时间满足条件后发送至 Worker 执行
    const MAX_PENDDING = 50;

    /**
     * @var DelayTaskPayload[]
     */
    private $jobs = [];

    /**
     * 当前等待任务数
     */
    private $count = 0;

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
                logger()->info('DelayTaskScheduler: 没有可用进程');
                return 1;
            }

            // 还可以继续填充等待任务
            if ($this->count < self::MAX_PENDDING) {
                 // 接收一个延时任务，并按照任务触发时间放入等待集合中
                 // 每次事件循环时通过对触发时间的比对确定该任务是否到达触发时间
                $params = $this->message->receiveByDelayTask(false);
                if ($params) {
                    $payload = (new DelayTaskPayload)->valid($params);
                    $triggerTime = $payload->getTriggerTime();
                    
                    // 按照触发时间聚合任务
                    $this->jobs[$triggerTime][] = $payload->toArray();
                    $this->count++;

                    logger()->info('DelayTaskScheduler: 延时任务进入等待调度', $payload->toArray());
                }
            }

            // 没有延时任务需要调度
            if (count($this->jobs) == 0) {
                return 0;
            }

            // 按照升序对等待任务进行排序
            // 这一布非必要，如果不执行下面触发时间检查将会将所有任务遍历比较
            ksort($this->jobs);

            // 当前事件，用于同任务触发时间做比对
            $time = time();

            /**
             * 对等待任务集合遍历比对确定任务是否满足执触发执行条件
             * 如果满足处罚执行条件，将符合的第一个任务发送至调度队列，等待 Worker 执行
             * （每次只触发一个，保证调度器公平）
             */
            $payloadData = null;
            foreach ($this->jobs as $triggerTime => $jobs) {
                if ($triggerTime <= $time) {
                    $payloadData = array_shift($this->jobs[$triggerTime]);

                    // $triggerTime 下所有任务被取出，删除该 key 空间
                    // 因为此处使用时间作比对，时间只会越来越大
                    if (empty($this->jobs[$triggerTime])) {
                        unset($this->jobs[$triggerTime]);
                    }

                    $this->count--;
                    break;
                }
            }

            // 重置任务集合内部指针
            reset($this->jobs);

            // 投递任务至调度队列
            if ($payloadData) {
                $this->sendJobToWorker($payloadData);
                logger()->info('DelayTaskScheduler: 延时任务进入调度队列', $payloadData);
                return 1;
            }

            return 0;
        } catch (Throwable $e) {
            Exception::handle($e);
        }
    }
}

