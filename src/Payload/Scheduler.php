<?php

namespace Xiaoe\Task\Payload;

use Xiaoe\Task\Exception\InvalidArgumentException;
use Xiaoe\Task\Exception\RuntimeException;
use Xiaoe\Task\UnitInterface;

/**
 * 任务调度（调度任务执行）数据载体
 */
class Scheduler implements PayloadInterface
{
    /**
     * 处理该任务的类
     *
     * @var string
     */
    protected $class;

    /**
     * 任务数据
     *
     * @var array
     */
    protected $data;

    /**
     * {@inheritDoc}
     */
    public function valid($params)
    {
        if (empty($params['class']) || ! class_exists($params['class'])) {
            throw new InvalidArgumentException('业务执行目标类未找到');
        }

        $this->class = $params['class'];
        $this->data  = $params['data'] ?? [];

        return $this;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 执行任务处理
     */
    public function call()
    {
        $unit = new $this->class;
        if ($unit instanceof UnitInterface) {
            $unit->handle($this->data);
        } else {
            throw new RuntimeException('业务执行目标类必须继承自 ' . UnitInterface::class);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return [
            'class' => $this->class,
            'data'  => $this->data,
        ];
    }
}
