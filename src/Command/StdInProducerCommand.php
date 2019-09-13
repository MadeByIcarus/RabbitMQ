<?php

namespace Icarus\RabbitMQ\Command;


use Icarus\RabbitMQ\AMQPMessageFactory;
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
class StdInProducerCommand extends Command
{
    protected static $defaultName = 'rabbitmq:stdin-producer';

    /**
     * @var RabbitMQ
     */
    private $rabbitMQ;



    public function __construct(RabbitMQ $rabbitMQ)
    {
        parent::__construct();
        $this->rabbitMQ = $rabbitMQ;
    }



    protected function configure()
    {
        $this
            ->setName('rabbitmq:stdin-producer')
            ->setDescription('Creates message from given STDIN and passes it to configured producer')
            ->addArgument('name', InputArgument::REQUIRED, 'Producer Name');
    }



    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $producer = $this->rabbitMQ->getProducer($input->getArgument('name'));

        $data = '';
        while (!feof(STDIN)) {
            $data .= fread(STDIN, 8192);
        }

        $message = AMQPMessageFactory::createMessage(serialize($data));
        $producer->publish($message);

        return 0;
    }

}
