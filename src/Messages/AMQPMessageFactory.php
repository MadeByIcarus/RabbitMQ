<?php
declare(strict_types=1);

namespace Icarus\RabbitMQ\Messages;


use Nette\Utils\Json;
use PhpAmqpLib\Message\AMQPMessage;


class AMQPMessageFactory
{

    public static function createMessage($body): AMQPMessage
    {
        return new AMQPMessage($body, ['content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
    }



    public static function createJsonMessage(JsonMessage $message): AMQPMessage
    {
        $jsonBody = $message->__toString();
        return new AMQPMessage($jsonBody, ['content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
    }
}