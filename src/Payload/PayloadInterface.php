<?php

namespace Xiaoe\Task\Payload;

/**
 * 队列消息数据载体接口
 */
interface PayloadInterface
{
    /**
     * 验证单条信息数据结构
     *
     * @param array $param
     * @return self
     */
    public function valid($params);

    /**
     * 将信息数据转换为数组
     *
     * @return array
     */
    public function toArray();
}
