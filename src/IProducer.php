<?php
declare(strict_types=1);

namespace Icarus\RabbitMQ;


use PhpAmqpLib\Message\AMQPMessage;


interface IProducer
{

    public function publish(AMQPMessage $message): void;

}