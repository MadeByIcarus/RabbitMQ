<?php
declare(strict_types=1);

namespace Icarus\RabbitMQ\DI;


use Icarus\RabbitMQ\Command\ConsumerCommand;
use Icarus\RabbitMQ\Command\SystemdServicesGenerator;
use Icarus\RabbitMQ\Command\StdInProducerCommand;
use Icarus\RabbitMQ\Connection\AMQPSSLLazyConnection;
use Icarus\RabbitMQ\Consumer;
use Icarus\RabbitMQ\Producer;
use Icarus\RabbitMQ\RabbitMQ;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use PhpAmqpLib\Connection\AMQPLazyConnection;


class RabbitMQExtension extends CompilerExtension
{

    private const CONSUMER_TAG = "consumer";
    private const PRODUCER_TAG = "producer";



    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'connection' => Expect::structure([
                'host' => Expect::string()->default('localhost'),
                'port' => Expect::string()->default(5671),
                'user' => Expect::string()->default('guest'),
                'password' => Expect::string()->default('guest'),
                'vhost' => Expect::string()->default('/'),
                'ssl' => Expect::anyOf(
                    false,
                    Expect::structure([
                        'cafile' => Expect::string(),
                        'local_cert' => Expect::string(),
                        'verify_peer' => Expect::bool()
                    ])
                )
            ]),
            'queues' => Expect::arrayOf(Expect::string()),
            'exchanges' => Expect::arrayOf(Expect::structure([
                'name' => Expect::string()->required()
            ])),
            'producers' => Expect::arrayOf(Expect::structure([
                'exchange' => Expect::string()->required(),
                'routingKey' => Expect::string()->default(null)
            ])),
            'consumers' => Expect::arrayOf(Expect::structure([
                'queue' => Expect::string()->required(),
                'processor' => Expect::string()->required(),
                'consumerTag' => Expect::string()->default(null)
            ])),
            'consumerServicesParameters' => Expect::arrayOf(Expect::string())
        ]);
    }



    public function loadConfiguration()
    {
        $config = $this->config;
        $builder = $this->getContainerBuilder();

        $connectionArgs = [
            $config->connection->host,
            $config->connection->port,
            $config->connection->user,
            $config->connection->password,
            $config->connection->vhost
        ];

        $connectionName = $this->prefix('AMQPConnection');
        $connectionClass = AMQPLazyConnection::class;

        if ($this->config->connection->ssl) {
            $args = array_filter((array)$config->connection->ssl, function ($value) {
                return !is_null($value);
            });
            $connectionArgs[] = $args;
            $connectionClass = AMQPSSLLazyConnection::class;
        }

        $builder
            ->addDefinition($connectionName)
            ->setFactory($connectionClass, $connectionArgs);

        //

        $builder->addDefinition($this->prefix('RabbitMQ'))
            ->setFactory(RabbitMQ::class)
            ->addSetup('setConsumerServicesParameters', [$config->consumerServicesParameters]);

        $builder->addDefinition($this->prefix('consumerCommand'))
            ->setFactory(ConsumerCommand::class);

        $builder->addDefinition($this->prefix('stdinProducer'))
            ->setFactory(StdInProducerCommand::class);

        $builder->addDefinition($this->prefix('systemdGenerator'))
            ->setFactory(SystemdServicesGenerator::class);
    }



    public function beforeCompile()
    {
        $config = $this->config;
        $builder = $this->getContainerBuilder();

        foreach ($config->producers as $name => $data) {
            $exchange = $config->exchanges[$data->exchange] ?? null;
            if (!$exchange) {
                throw new \InvalidArgumentException("Unknown exchange '" . $data->exchange . "'.");
            }
            $exchangeName = $exchange->name;
            $routingKey = $data->routingKey;
            $name = 'producer.' . $name;

            $builder->addDefinition($this->prefix($name))
                ->setFactory(Producer::class, [$exchangeName, $routingKey])
                ->addTag(self::PRODUCER_TAG);
        }

        foreach ($config->consumers as $name => $data) {
            $queue = $config->queues[$data->queue] ?? null;
            if (!$queue) {
                throw new \InvalidArgumentException("Unknown queue '" . $data->queue . "'.");
            }
            $handlerClass = $data->handler;
            $handlerDefinition = $builder->getDefinitionByType($handlerClass);
            $handlerServiceName = $handlerDefinition->getName();
            $consumerTag = $data->consumerTag;

            $name = 'consumer.' . $name;
            $builder->addDefinition($this->prefix($name))
                ->setFactory(Consumer::class, [$queue, $consumerTag, '@' . $handlerServiceName])
                ->addTag(self::CONSUMER_TAG);
        }
    }
}