<?php

namespace Xiaoe\Task\Process;

use Arara\Process\Action\Callback;
use Arara\Process\Context;
use Arara\Process\Control;
use Throwable;
use Xiaoe\Task\Exception\Exception;
use Xiaoe\Task\Message\Ipc;
use Xiaoe\Task\Message\MessageInterface;
use Xiaoe\Task\Payload\Scheduler as SchedulerPayload;
use Xiaoe\Task\Payload\Exchange as ExchangePayload;

/**
 * 工作进程（作业容器）
 * 负责作业队列通信，接收新任务，上报进程状态（繁忙、空闲）
 */
class Worker extends Callback implements WorkerInterface
{
    /**
     * 当前进程已执行任务数量
     *
     * @var int
     */
    protected $executedCount = 0;

    /**
     * 最大任务执行次数
     *
     * 当 $execCount 超过该值时进程自定退出（内存回收，防止内存残留过多）
     * 当前进程退出后父进程会自动重新拉起子进程
     *
     * @var int
     */
    protected $maxExecCount = 4000;

    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     * 覆盖父类析构方法，进程回调事件手动调用
     */
    public function __construct() {}

    /**
     * 进程启动回调方法
     * 打开进程工作队列连接，Worker 运行环境初始化
     *
     * @param int $event
     * @param Control $control
     * @param Context $context
     */
    public function onStart($event, Control $control, Context $context)
    {
        // 进程内部默认使用 IPC 通信
        $this->message = new Ipc();
        // 进程启动时发送进程闲置状态
        $this->reportStatus($context->processId, self::STATUS_IDLE);
    }

    /**
     * {@inheritDoc}
     */
    public function execute(Control $control, Context $context)
    {
        $pid = $context->processId;

        while (true) {
            if ($this->executedCount >= $this->maxExecCount) {
                logger("Worker: 任务处理达到最大次数，进程({$pid})即将推出");
                break;
            }

            try {
                // 获取带处理任务
                $params = $this->message->receiveByScheduler(false);
                if ($params) {
                    logger()->info('Worker: 执行完调度任务', $params);

                    // 上报当前 Worker 状态为繁忙
                    $this->reportStatus($pid, self::STATUS_BUSY);

                    // 通过参数执行对应业务单元
                    (new SchedulerPayload)->valid($params)->call();
                    $this->executedCount++;

                    // 上报当前 Worker 状态为空闲
                    $this->reportStatus($pid, self::STATUS_IDLE);
                }
            } catch (Throwable $e) {
                Exception::handle($e);
                // 上报当前 Worker 状态为空闲
                $this->reportStatus($pid, self::STATUS_IDLE);
            }
            
            // 休息一下，防止循环过快导致 message 处于频繁读取状态
            $control->flush(0.1);
            // 对信号作对应处理
            $control->signal()->dispatch();
        }
    }

    /**
     * PHP Error 回调方法
     *
     * @param int $event
     * @param Control $control
     * @param Context $context
     */
    public function onError($event, Control $control, Context $context)
    {
        Exception::handle($context->exception);
    }

    /**
     * exception 异常回调方法
     *
     * @param int $event
     * @param Control $control
     * @param Context $context
     */
    public function onFailure($event, Control $control, Context $context)
    {
        Exception::handle($context->exception);
    }

    /**
     * 进程推出
     *
     * @param int $event
     * @param Control $control
     * @param Context $context
     */
    public function onFinish($event, Control $control, Context $context)
    {
        $this->reportStatus($context->processId, self::STATUS_EXIT);
    }

    /**
     * 上报进程状态
     *
     * @param int $pid
     * @param int $status
     * @return bool
     */
    protected function reportStatus($pid, $status)
    {
        $params = [
            'pid'    => $pid,
            'status' => $status,
        ];
        $payloadData = (new ExchangePayload)->valid($params)->toArray();

        return $this->message->sendForExchange($payloadData);
    }
}

