<?php
declare(strict_types=1);

namespace Icarus\RabbitMQ;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;


class Producer implements IProducer
{

    /**
     * @var string
     */
    private $exchange;

    /**
     * @var string
     */
    private $queue;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var RabbitMQ
     */
    private $rabbitMQ;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var IConfirmHandler
     */
    private $confirmHandler;



    public function __construct(
        string $exchange,
        string $routingKey,
        RabbitMQ $rabbitMQ,
        IConfirmHandler $confirmHandler
    )
    {
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
        $this->rabbitMQ = $rabbitMQ;
        $this->confirmHandler = $confirmHandler;
    }



    private function getChannel(): AMQPChannel
    {
        if (!$this->channel) {
            $this->channel = $this->rabbitMQ->getConnection()->channel();

            $this->channel->confirm_select(true);

            $this->channel->set_ack_handler(function (AMQPMessage $message) {
                $this->confirmHandler->handleAck($message);
            });
            $this->channel->set_nack_handler(function (AMQPMessage $message) {
                $this->confirmHandler->handleNack($message);
            });
        }
        return $this->channel;
    }



    public function addToBatch(AMQPMessage $message): void
    {
        $this->getChannel()->batch_basic_publish(
            $message,
            $this->exchange,
            $this->routingKey
        );
    }



    public function publishBatch(): void
    {
        $this->getChannel()->publish_batch();
        $this->getChannel()->wait_for_pending_acks();
    }



    public function publish(AMQPMessage $message): void
    {
        $this->getChannel()->basic_publish(
            $message,
            $this->exchange,
            $this->routingKey
        );

        $this->getChannel()->wait_for_pending_acks();
    }
}