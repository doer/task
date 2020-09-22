<?php

namespace Xiaoe\Task\Process;

use Arara\Process\Action\Action;
use Arara\Process\Child;
use Arara\Process\Control;

/**
 * 进程池管理
 *
 * @date 2020-05-07
 */
class Pool implements PoolInterface
{
    /**
     * 进程池最大进程数
     *
     * @var int
     */
    protected $max;

    /**
     * 进程池最小进程数
     *
     * @var int
     */
    protected $mini;

    /**
     * 进程池进程与状态映射
     * $workers = ['pid' => Worker::STATUS, ...]
     *
     * @var int[]
     */
    protected $workers = [];

    /**
     * @var Control $control
     */
    protected $control;

    /**
     * @param Control $control
     * @param int $mini
     * @param array $options
     */
    public function __construct(Control $control, $mini, $options = [])
    {
        $this->max  = $options['max'] ?? 64;
        $this->mini = $mini;

        $this->control = $control;
    }

    /**
     * {@inheritDoc}
     */
    public function recovery()
    {
        foreach ($this->workers as $pid => $status) {
            $alive = $this->control->signal()->send(0, $pid);
            if (! $alive) {
                $this->removeWorker($pid);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function available()
    {
        $idle = $this->getIdleSize();
        if ($idle) {
            return true;
        }

        // 进程池进程数未达到最大限制，扩展进程池
        if ($this->count() < $this->max) {
            $this->expand(1);
            return true;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->workers);
    }

    /**
     * {@inheritDoc}
     */
    public function getIdleSize()
    {
        $idle = 0;
        foreach ($this->workers as $status) {
            if ($status == WorkerInterface::STATUS_IDLE) {
                $idle++;
            }
        }

        return $idle;
    }

    /**
     * {@inheritDoc}
     */
    public function getBusySize()
    {
        $busy = 0;
        foreach ($this->workers as $status) {
            if ($status == WorkerInterface::STATUS_BUSY) {
                $busy++;
            }
        }

        return $busy;
    }

    /**
     * 扩充进程池进程数
     *
     * @param int $size
     */
    public function expand($size)
    {
        logger()->info("Pool: 扩展进程池({$size})");
        $control = new Control;

        // 工作进程绑定，绑定回调事件
        $action  = new Worker;
        // Worker 启动回调事件
        $action->bind(Action::EVENT_START, [$action, 'onStart']);
        // Worker 业务处理失败（例如 USER_NOTICE 等）错误回调事件
        $action->bind(Action::EVENT_ERROR, [$action, 'onError']);
        // Worker 业务处理产生一场回调事件
        $action->bind(Action::EVENT_FAILURE, [$action, 'onFailure']);
        // Worker 进程生命周期结束（进程退出）回调事件
        $action->bind(Action::EVENT_FINISH, [$action, 'onFinish']);

        // 启动子进程
        for ($i = 0; $i < $size; $i++) {
            (new Child($action, $control))->start();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function shrink()
    {
        if ($this->count() <= $this->mini) {
            return false;
        }

        foreach ($this->workers as $pid => $status) {
            if ($status == WorkerInterface::STATUS_IDLE) {
                // 向子进程发送结束运行信号
                // 等待子进程发出退出信号后将子进程从进程池移除
                $this->control->signal()->send('terminate', $pid);
                logger()->info("Pool: 释放子进程({$pid})，等待其自行退出");
                break;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function setWorkerStatus($pid, $status)
    {
        switch ($status) {
            case WorkerInterface::STATUS_IDLE: // 进程空闲
            case WorkerInterface::STATUS_BUSY: // 进程繁忙
                $isRuning = $this->control->signal()->send(0, $pid);
                if ($isRuning) {
                    $this->workers[$pid] = $status;
                }
                break;

            case WorkerInterface::STATUS_EXIT: // 子进程退出
                $this->removeWorker($pid);
                break;
        }
    }

    /**
     * 移除进程信息
     *
     * @param int $pid
     */
    private function removeWorker($pid)
    {
        unset($this->workers[$pid]);
    }

    /**
     * {@inheritDoc}
     */
    public function terminate()
    {
        logger()->info('Pool: 等待所有子进程自行退出');

        foreach ($this->workers as $pid => $status) {
            logger()->info("Pool: terminate 子进程({$pid})，等待其自行退出");
            $this->control->signal()->send('terminate', $pid);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function stop()
    {
        logger()->info('Pool: 强制结束所有紫禁城');

        foreach ($this->workers as $pid => $status) {
            logger()->info("Pool: kill 子进程({$pid})，等待其自行退出");
            $this->control->signal()->send('kill', $pid);
        }
    }
}

