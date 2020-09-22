<?php

namespace Xiaoe\Task;

use Throwable;
use Xiaoe\Task\Exception\Exception;
use Xiaoe\Task\Message\Ipc;
use Xiaoe\Task\Process\PoolInterface;
use Xiaoe\Task\Scheduler\ProcessScheduler;
use Xiaoe\Task\Scheduler\SchedulerInterface;

class Event
{
    /**
     * @var PoolInterface
     */
    protected $processPool;

    /**
     * @var SchedulerInterface[]
     */
    protected $schedulers = [];

    /**
     * @param array $schedulers
     */
    public function __construct($schedulers)
    {
        // 进程调度器，内部实例化不对外暴露
        $this->attachScheduler(new ProcessScheduler(new Ipc));

        foreach ($schedulers as $scheduler) {
            $this->attachScheduler($scheduler);
        }
    }

    /**
     * 为事件管理器添加调度器
     */
    public function attachScheduler(SchedulerInterface $scheduler)
    {
        $hash = spl_object_hash($scheduler);
        if (! isset($this->schedulers[$hash])) {
            $this->schedulers[$hash] = $scheduler;
        }
    }

    /**
     * 移除任务调度器
     */
    public function detchScheduler(SchedulerInterface $scheduler)
    {
        $hash = spl_object_hash($scheduler);
        unset($this->schedulers[$hash]);
    }

    /**
     * 返回所有调度器
     *
     * @return SchedulerInterface[]
     */
    public function getSchedulers()
    {
        return $this->schedulers;
    }

    /**
     * 返回调度器数量（减掉进程调度器）
     * 此数据决定进程池最小进程数
     * 因为进程调度器不需要进程执行业务，所以减掉
     *
     * @return int
     */
    public function getSchedulerCount()
    {
        return count($this->schedulers) - 1;
    }

    /**
     * 为调度器设置进程池管理对象
     */
    public function setProcessPool(PoolInterface $pool)
    {
        $this->processPool = $pool;
    }

    /**
     * 获取进程池管理对象
     *
     * @return PoolInterface
     */
    public function getProcessPool()
    {
        return $this->processPool;
    }

    /**
     * 补充调度器
     */
    public function replenishScheduler()
    {
        foreach ($this->schedulers as $scheduler) {
            $scheduler->setProcessPool($this->processPool);
        }
    }

    /**
     * 开始事件循环
     */
    public function loop()
    {
        try {
            // 调度器需要进程数（总计）
            $need = 0;

            // 执行遍历调度所有调度器
            foreach ($this->schedulers as $scheduler) {
                $need += $scheduler->handle();
            }

            // 所有调度器都不需要进程工作
            // 表示进程池有闲置，回收进程
            if ($need == 0) {
                $this->processPool->shrink();
            }
        } catch (Throwable $e) {
            // 发生异常，记录日志
            Exception::handle($e);
        }
    }
}

