<?php
declare(strict_types=1);

namespace Icarus\RabbitMQ;


use PhpAmqpLib\Message\AMQPMessage;


interface IIncomingMessageHandler
{

    public function handle(AMQPMessage $message): int;
}