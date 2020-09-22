<?php

namespace Xiaoe\Task\Payload;

/**
 * 延时异步任务数据载体
 */
class DelayTask extends Scheduler implements PayloadInterface
{
    /**
     * 任务执行触发时间
     *
     * @var int
     */
    protected $trigger;

    /**
     * {@inheritDoc}
     */
    public function valid($params)
    {
        parent::valid($params);

        $this->trigger = $params['trigger'] ?? time();

        return $this;
    }

    /**
     * @return int
     */
    public function getTriggerTime()
    {
        return $this->trigger;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        $array            = parent::toArray();
        $array['trigger'] = $this->trigger;

        return $array;
    }
}
