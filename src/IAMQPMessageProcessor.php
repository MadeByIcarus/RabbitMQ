<?php
declare(strict_types=1);

namespace Icarus\RabbitMQ;


use PhpAmqpLib\Message\AMQPMessage;


interface IAMQPMessageProcessor
{

    public function process(AMQPMessage $message): int;
}