<?php

namespace Xiaoe\Task;

use Arara\Process\Action\Daemon;
use Arara\Process\Child;
use Arara\Process\Control;
use Psr\Log\LoggerInterface;
use Xiaoe\Task\Process\Pool;

class Server
{
    /**
     * @var array 服务配置 eg: ['server' => [...], 'pool' => [...]]
     */
    protected $options = [];

    /**
     * @var Event
     */
    protected $event;

    /**
     * @var LoggerInterface
     */
    protected static $logger;

    /**
     * @param Event $event
     * @param AbstractLogger $logger
     * @param array $options
     */
    public function __construct(Event $event, LoggerInterface $logger, $options = [])
    {
        $this->options['server'] = $options['server'] ?? [];
        $this->options['pool']   = $options['pool'] ?? [];

        $this->event  = $event;
        self::$logger = $logger;
    }

    /**
     * 获取日志记录器
     *
     * @return AbstractLogger
     */
    public static function getLogger()
    {
        return self::$logger;
    }

    /**
     * 当前进程转变为守护进程
     * 进程池对象初始化，注册服务启动回调方法
     *
     * @param string $args
     */
    public function start($command)
    {
        // 启动进程，并切换为守护进程模式
        $action  = new Daemon([$this, 'bootstrap'], $this->options['server']);
        $control = new Control;

        // 通过命令行对服务进程控制
        $child = new Child($action, $control);
        switch ($command) {
            case 'start':
                $child->start();
                break;

            case 'stop':
                $child->terminate();
                break;
            
            default:
                die('php task {start|stop|status}');
        }
    }

    /**
     * 服务启动
     *
     * @param Control $control
     */
    public function bootstrap(Control $control)
    {
        // 初始化进程池管理对象
        $processPool = new Pool(
            $control,
            $this->event->getSchedulerCount(),
            $this->options['pool']
        );

        // 为服务退出信号注册进程池终结事件
        $control->signal()->prependHandler(SIGTERM, [$processPool, 'terminate']);

        // 为事件管理/调度器设置进程池管理对象
        $this->event->setProcessPool($processPool);
        $this->event->replenishScheduler();

        while (true) {
            // 处理进程信号 自行处理信号
            $control->signal()->dispatch();
            // 休息一下，防止循环过快导致 message 处于频繁读取状态
            // 同时给内核时间回收已退出子进程
            $control->flush(0.01);
            // 开始事件循环（轮训调度器）
            $this->event->loop();
        }
    }
}

