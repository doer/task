<?php

namespace Xiaoe\Task\Payload;

use Xiaoe\Task\Exception\InvalidArgumentException;

/**
 * 进程通信消息数据载体
 */
class Exchange implements PayloadInterface
{
    /**
     * @var int
     */
    protected $pid;

    /**
     * @var int
     */
    protected $status;

    /**
     * {@inheritDoc}
     */
    public function valid($params)
    {
        if (empty($params['pid'])) {
            throw new InvalidArgumentException('pid 不能为空');
        }
        if (empty($params['status'])) {
            throw new InvalidArgumentException('status 值不能为空');
        }

        $this->pid    = $params['pid'];
        $this->status = $params['status'];

        return $this;
    }

    /**
     * @var int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return [
            'pid' => $this->pid,
            'status' => $this->status,
        ];
    }
}

