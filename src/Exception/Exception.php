<?php

namespace Xiaoe\Task\Exception;

use Throwable;

class Exception extends \Exception
{
    /**
     * 提取/处理异常
     */
    public static function handle(Throwable $e)
    {
        logger()->error($e->getMessage());
    }
}

