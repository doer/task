<?php

namespace Xiaoe\Task\Process;

interface PoolInterface
{
    /**
     * 从进程池中移除所有已尽退出子进程
     */
    public function recovery();

    /**
     * 获取进程池大小
     */
    public function count();

    /**
     * 返回进程池是否有可用（空闲）进程
     * 如果当前没有可用进程且进程池实际进程数小于最大进程数，扩充进程池
     *
     * @return bool
     */
    public function available();

    /**
     * 返回空闲进程数
     *
     * @var int
     */
    public function getIdleSize();

    /**
     * 返回繁忙进程数
     *
     * @var int
     */
    public function getBusySize();

    /**
     * 停止进程池，发送退出信号
     * 等待进程池所有子进程处理完业务后自行退出
     */
    public function terminate();

    /**
     * 停止进程池，强制退出
     * 向进程池所有进程发送 kill -9 强行终止子进程
     */
    public function stop();

    /**
     * 回收进程
     *
     * @param int $size
     * @return bool
     */
    public function shrink();

    /**
     * 设置 Worker 进程状态
     *
     * @param int $pid
     * @param int $status
     */
    public function setWorkerStatus($pid = null, $status);
}

