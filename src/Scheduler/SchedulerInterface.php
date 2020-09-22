<?php

namespace Xiaoe\Task\Scheduler;

use Xiaoe\Task\Process\PoolInterface;

interface SchedulerInterface
{
    /**
     * 设置进程池管理对象
     *
     * @var PoolInterface $pool
     */
    public function setProcessPool(PoolInterface $pool);

    /**
     * 调度业务处理
     *
     * @return int 返回 1 表示需要进程（不论是否有空闲进程），否则返回 0
     */
    public function handle();
}
