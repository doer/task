<?php

namespace Xiaoe\Task\Message;

/**
 * IPC 通信
 */
class Ipc implements MessageInterface
{
    /**
     * @var int
     */
    protected $key;

    /**
     * the System V message queue
     * 
     * @var resource
     */
    protected $queue;

    /**
     * @param int $type
     * @param string $key
     */
    public function __construct($key = '')
    {
        // 默认使用当前文件作为 key 生成 ftok 资源符
        if (! $key) {
            $key = __FILE__;
        }
        $this->key = ftok($key, 'i');

        // 创建或者打开一个队列连接
        $this->queue = msg_get_queue($this->getKey());
    }

    /**
     * {@inheritDoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        $stat = msg_stat_queue($this->queue);
        return $stat['msg_qnum'];
    }

    /**
     * {@inheritDoc}
     */
    public function receive($queueType = self::TYPE_NORMAL_TASK, $block = false)
    {
        if ($block) {
            msg_receive($this->queue, $queueType, $messageType, 8192, $message, true);
        } else {
            msg_receive($this->queue, $queueType, $messageType, 8192, $message, true, MSG_IPC_NOWAIT);
        }
        
        return $message;
    }

    /**
     * {@inheritDoc}
     */
    public function receiveByNormalTask($block = false)
    {
        return $this->receive(self::TYPE_NORMAL_TASK, $block);
    }

    /**
     * {@inheritDoc}
     */
    public function receiveByDelayTask($block = false)
    {
        return $this->receive(self::TYPE_DELAY_TASK, $block);
    }

    /**
     * {@inheritDoc}
     */
    public function receiveByScheduler($block = false)
    {
        return $this->receive(self::TYPE_SCHEDULER, $block);
    }

    /**
     * {@inheritDoc}
     */
    public function receiveByExchange($block = false)
    {
        return $this->receive(self::TYPE_EXCHANGE, $block);
    }

    /**
     * {@inheritDoc}
     */
    public function send($data, $queueType = self::TYPE_NORMAL_TASK)
    {
        //
        // FIXME $data 内容体过大，需使用使用共享内存船体
        //
        return msg_send($this->queue, $queueType, $data, true, false);
    }

    /**
     * {@inheritDoc}
     */
    public function sendForNormalTask($data)
    {
        return $this->send($data, self::TYPE_NORMAL_TASK);
    }

    /**
     * {@inheritDoc}
     */
    public function sendForDelayTask($data)
    {
        return $this->send($data, self::TYPE_DELAY_TASK);
    }

    /**
     * {@inheritDoc}
     */
    public function sendForExchange($data)
    {
        return $this->send($data, self::TYPE_EXCHANGE);
    }

    /**
     * {@inheritDoc}
     */
    public function sendToWorker($data)
    {
        return $this->send($data, self::TYPE_SCHEDULER);
    }
}
