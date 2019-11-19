<?php
declare(strict_types=1);

namespace Icarus\RabbitMQ;


use PhpAmqpLib\Message\AMQPMessage;


interface IConsumer
{

    const MESSAGE_ACK = 1;
    const MESSAGE_NACK = 2;
    const MESSAGE_REJECT = 3;
    const MESSAGE_REJECT_AND_TERMINATE = 4;



    public function consume(?int $maxMessages, ?int $maxExecutionTime, ?int $maxMemoryLimit): void;
}