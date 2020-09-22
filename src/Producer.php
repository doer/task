<?php

namespace Xiaoe\Task;

use Xiaoe\Task\Message\MessageInterface;
use Xiaoe\Task\Payload\NormalTask as NormalTaskPayload;
use Xiaoe\Task\Payload\DelayTask as DelayTaskPayload;

/**
 * 消息生产者
 */
class Producer implements ProducerInterface
{
    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     * @param MessageInterface $message
     */
    public function __construct(MessageInterface $message)
    {
        $this->message = $message;
    }

    /**
     * {@inheritDoc}
     */
    public function task($class, $data, $chunkSize = null)
    {
        $success = true;

        if ($chunkSize) {
            $chunks = array_chunk($data, $chunkSize);
        } else {
            $chunks = (array)$data;
        }

        foreach ($chunks as $value) {
            $params = [
                'class' => $class,
                'data'  => $value
            ];
            $messageData = (new NormalTaskPayload)
                ->valid($params)
                ->toArray()
            ;

            $result = $this->message->sendForNormalTask($messageData);
            if (! $result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritDoc}
     */
    public function delayTask($delayTime, $class, $data, $chunkSize = null)
    {
        $success = true;

        if ($chunkSize) {
            $chunks = array_chunk($data, $chunkSize);
        } else {
            $chunks = (array)$data;
        }

        $trigger = time() + $delayTime;
        foreach ($chunks as $value) {
            $params = [
                'class'   => $class,
                'data'    => $value,
                'trigger' => $trigger,
            ];
            $messageData = (new DelayTaskPayload)
                ->valid($params)
                ->toArray()
            ;

            $result = $this->message->sendForDelayTask($messageData);
            if (! $result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return $this->message->count();
    }
}

