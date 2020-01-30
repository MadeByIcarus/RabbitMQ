<?php
declare(strict_types=1);

namespace Icarus\RabbitMQ;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;


class Consumer implements IConsumer
{

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var RabbitMQ
     */
    private $rabbitMQ;

    /**
     * @var IIncomingMessageHandler
     */
    private $messageHandler;

    private $consumerTag;

    private $consumedMessagesCount = 0;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var bool
     */
    private $forceStop = false;

    /**
     * @var int|null
     */
    private $maxMessageCount;

    /**
     * @var int|null
     */
    private $maxExecutionTime;

    /**
     * @var int
     */
    private $executionStartedTimestamp;

    /**
     * @var int|null
     */
    private $memoryLimit;



    public function __construct(
        string $queueName,
        ?string $consumerTag,
        IIncomingMessageHandler $messageHandler,
        RabbitMQ $rabbitMQ
    )
    {
        $this->queueName = $queueName;
        $this->messageHandler = $messageHandler;
        $this->rabbitMQ = $rabbitMQ;
        $this->consumerTag = empty($consumerTag) ? sprintf("PHPPROCESS_%s_%s", gethostname(), getmypid()) : $consumerTag;
    }



    public function consume(?int $maxMessages, ?int $maxExecutionTime, ?int $maxMemoryLimit): void
    {
        $this->executionStartedTimestamp = time();
        $this->maxMessageCount = $maxMessages;
        $this->maxExecutionTime = $maxExecutionTime;
        $this->memoryLimit = $maxMemoryLimit;
        $this->setup();

        $timeout = 5;// debug $maxExecutionTime;

        while ($this->getChannel()->is_consuming()) {
            if ($this->shouldStop()) {
                $timeout = 1;
            }

            try {
                $this->getChannel()->wait(null, false, $timeout);
                usleep(1000);
            } catch (AMQPTimeoutException $e) {
                // intentionally not throwing the exception
            }
        }
    }



    public function consumeMessage(AMQPMessage $message): void
    {
        /** @var AMQPChannel $channel */
        $channel = $message->delivery_info['channel'];
        $deliveryTag = $message->delivery_info['delivery_tag'];

        $result = $this->messageHandler->handle($message);

        switch ($result) {
            case IConsumer::MESSAGE_ACK:
                $channel->basic_ack($deliveryTag);
                break;
            case IConsumer::MESSAGE_NACK:
                $channel->basic_nack($deliveryTag, false, true);
                break;
            case IConsumer::MESSAGE_REJECT:
                $channel->basic_reject($deliveryTag, false);
                break;
        }

        $this->consumedMessagesCount++;
        $this->shouldStop();
    }



    protected function setup()
    {
        $this->getChannel()->basic_consume(
            $this->queueName,
            $this->getConsumerTag(),
            false,
            false,
            false,
            false,
            [$this, 'consumeMessage']
        );
    }



    protected function shouldStop()
    {
        if (
            $this->forceStop
            ||
            ($this->maxExecutionTime && (time() - $this->executionStartedTimestamp) >= $this->maxExecutionTime)
            ||
            ($this->maxMessageCount > 0 && $this->maxMessageCount <= $this->consumedMessagesCount)
            ||
            !$this->isMemoryUsageOk()
        ) {
            $this->stop();
            return true;
        }
        return false;
    }



    protected function stop(): void
    {
        $this->getChannel()->basic_cancel($this->getConsumerTag(), true);
    }



    protected function isMemoryUsageOk(): bool
    {
        if (!$this->memoryLimit) {
            return true;
        }

        return memory_get_usage(true) < ($this->memoryLimit - 5) * 1024 * 1024;
    }



    protected function getChannel(): AMQPChannel
    {
        if (!$this->channel) {
            $this->channel = $this->rabbitMQ->getConnection()->channel();
        }
        return $this->channel;
    }



    public function getConsumerTag(): string
    {
        return $this->consumerTag;
    }

}