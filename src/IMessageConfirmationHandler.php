<?php
declare(strict_types=1);

namespace Icarus\RabbitMQ;


use PhpAmqpLib\Message\AMQPMessage;


interface IMessageConfirmationHandler
{

    public function handleAck(AMQPMessage $message);



    public function handleNack(AMQPMessage $message);
}