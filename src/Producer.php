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
     * @var IMessageConfirmationHandler
     */
    private $confirmationHandler;



    public function __construct(
        string $exchange,
        string $routingKey,
        RabbitMQ $rabbitMQ,
        IMessageConfirmationHandler $confirmationHandler
    )
    {
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
        $this->rabbitMQ = $rabbitMQ;
        $this->confirmationHandler = $confirmationHandler;
    }



    private function getChannel(): AMQPChannel
    {
        if (!$this->channel) {
            $this->channel = $this->rabbitMQ->getConnection()->channel();

            $this->channel->confirm_select();

            $this->channel->set_ack_handler(function (AMQPMessage $message) {
                $this->confirmationHandler->handleAck($message);
            });
            $this->channel->set_nack_handler(function (AMQPMessage $message) {
                $this->confirmationHandler->handleNack($message);
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