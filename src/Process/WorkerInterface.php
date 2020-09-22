<?php

namespace Xiaoe\Task\Process;

interface WorkerInterface
{
    // 进程状态 - 空闲
    const STATUS_IDLE = 1;

    // 进程状态 - 繁忙
    const STATUS_BUSY = 2;

    // 进程状态 - 退出
    const STATUS_EXIT = 4;
}

