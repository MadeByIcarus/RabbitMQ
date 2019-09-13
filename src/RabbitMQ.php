<?php
declare(strict_types=1);

namespace Icarus\RabbitMQ;


use Nette\DI\Container;
use PhpAmqpLib\Connection\AMQPStreamConnection;


class RabbitMQ
{

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $consumerServicesParameters;



    public function __construct(
        AMQPStreamConnection $connection,
        Container $container
    )
    {
        $this->container = $container;
        $this->connection = $connection;
        register_shutdown_function(function () {
            $this->connection->close();
        });
    }



    public function getConnection(): AMQPStreamConnection
    {
        return $this->connection;
    }



    public function getConsumer(string $name): Consumer
    {
        $name = "rabbitmq.consumer." . $name;
        if (!($consumer = $this->container->getService($name)) || !$consumer instanceof Consumer) {
            throw new ConsumerNotFound("Consumer '$name' does not exist.");
        }
        return $consumer;
    }



    public function getProducer(string $name): Producer
    {
        $name = "rabbitmq.producer." . $name;
        if (!($producer = $this->container->getService($name)) || !$producer instanceof Producer) {
            throw new ProducerNotFound("Producer '$name' does not exist.");
        }
        return $producer;
    }



    /**
     * @return array
     */
    public function getConsumerServicesParameters(): array
    {
        return $this->consumerServicesParameters;
    }



    /**
     * @param array $consumerServicesParameters
     */
    public function setConsumerServicesParameters(array $consumerServicesParameters): void
    {
        $this->consumerServicesParameters = $consumerServicesParameters;
    }

}