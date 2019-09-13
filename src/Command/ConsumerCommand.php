<?php

namespace Icarus\RabbitMQ\Command;


use Icarus\RabbitMQ\Consumer;
use Icarus\RabbitMQ\RabbitMQ;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class ConsumerCommand extends Command
{

    protected static $defaultName = 'rabbitmq:consumer';

    /**
     * @var Consumer
     */
    protected $consumer;

    /**
     * @var int
     */
    protected $amount;

    /**
     * @var RabbitMQ
     */
    private $rabbitMQ;

    /**
     * @var bool|string|string[]|null
     */
    private $memoryLimit;



    public function __construct(RabbitMQ $rabbitMQ)
    {
        parent::__construct();

        $this->rabbitMQ = $rabbitMQ;
    }



    protected function configure()
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Consumer Name')
            ->addOption('messages', 'm', InputOption::VALUE_OPTIONAL, 'Messages to consume', 0)
            ->addOption('route', 'r', InputOption::VALUE_OPTIONAL, 'Routing Key', '')
            ->addOption('memory-limit', 'l', InputOption::VALUE_OPTIONAL, 'Allowed memory for this process', null);
    }



    /**
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @throws \InvalidArgumentException When the number of messages to consume is less than 0
     * @throws \BadFunctionCallException When the pcntl is not installed and option -s is true
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        if (($this->amount = $input->getOption('messages')) < 0) {
            throw new \InvalidArgumentException("The -m option should be null or greater than 0");
        }

        if (($this->memoryLimit = $input->getOption('memory-limit')) <= 0 && !is_null($this->memoryLimit)) {
            throw new \InvalidArgumentException("The -l option should be null or greater than 0");
        }

        $this->consumer = $this->rabbitMQ->getConsumer($input->getArgument('name'));
    }



    /**
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->consumer->consume($this->amount, $this->memoryLimit);
        return 0;
    }

}
