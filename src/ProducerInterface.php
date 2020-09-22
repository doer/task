<?php

namespace Xiaoe\Task;

/**
 * 异步任务生产者接口
 * 负责将接口/定时等业务中需要并行处理任务异步投递
 *
 * @author russell
 * @date 2020-04-27
 */
interface ProducerInterface
{
    /**
     * 投递任务
     *
     * @param string $class 执行任务目标类，eg：\App\Services\Check:class
     * @param array $edata 待处理数据
     * @param int $chunk 将数据分成多分并行执，例如待处理数据为 1w，$chunk = 1000 时 表示数据分成 10 份并行处理
     *                   在定时任务中，处理任务的进程为动态，会根据任务数据自动增加
     *                   在 php-fpm 中处理任务的进程数是固定的，如果没有可工作进程任务将会进入等待直到被处理完
     *
     * @return bool
     */
    public function task($class, $data, $chunk = null);

    /**
     * 投递延时任务
     *
     * @param string $class 执行任务目标类，eg：\App\Services\Check:class
     * @param array $edata 待处理数据
     * @param int $chunk 将数据分成多分并行执，例如待处理数据为 1w，$chunk = 1000 时 表示数据分成 10 份并行处理
     *                   在定时任务中，处理任务的进程为动态，会根据任务数据自动增加
     *                   在 php-fpm 中处理任务的进程数是固定的，如果没有可工作进程任务将会进入等待直到被处理完
     *
     * @return bool
     */
    public function delayTask($delayTime, $class, $data, $chunk = null);

    /**
     * 获取队列长度（等待处理）
     *
     * @return int
     */
    public function count();
}

