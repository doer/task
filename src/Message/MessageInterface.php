<?php

namespace Xiaoe\Task\Message;

/**
 * 消息接口，所有通信消息必须继承该类
 */
interface MessageInterface
{
    // 队列任务类型
    // 普通任务
    const TYPE_NORMAL_TASK = 1;
    // 延时任务
    const TYPE_DELAY_TASK  = 2;
    // 任务分发
    const TYPE_SCHEDULER   = 4;
    // 进程通信
    const TYPE_EXCHANGE    = 8;

    /**
     * 队列 key
     *
     * @return int
     */
    public function getKey();

    /**
     * 队列任务数
     *
     * @return int
     */
    public function count();

    /**
     * 接收消息
     *
     * @param int $type
     * @param bool $block
     * @return array|false
     */
    public function receive($type = self::TYPE_NORMAL_TASK, $block = false);

    /**
     * @param bool $block
     * @return array|false
     */
    public function receiveByNormalTask($block = false);

    /**
     * @param bool $block
     * @return array|false
     */
    public function receiveByDelayTask($block = false);

    /**
     * @param bool $block
     * @return array|false
     */
    public function receiveByScheduler($block = false);

    /**
     * @param bool $block
     * @return array|false
     */
    public function receiveByExchange($block = false);

    /**
     * 发送一条消息至消息队列
     *
     * @param array $data
     * @param int $type
     * @return bool
     */
    public function send($data, $type = self::TYPE_NORMAL_TASK);

    /**
     * @param array $data
     * @return bool
     */
    public function sendForNormalTask($data);

    /**
     * @param array $data
     * @return bool
     */
    public function sendForDelayTask($data);

    /**
     * @param array $data
     * @return bool
     */
    public function sendForExchange($data);

    /**
     * @param array $data
     * @return bool
     */
    public function sendToWorker($data);
}

