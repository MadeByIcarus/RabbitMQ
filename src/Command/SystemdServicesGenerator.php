<?php

namespace Icarus\RabbitMQ\Command;


use Icarus\RabbitMQ\Consumer;
use Icarus\RabbitMQ\RabbitMQ;
use Nette\Utils\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class SystemdServicesGenerator extends Command
{

    protected static $defaultName = 'rabbitmq:systemd-services-generator';

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
        $this->addOption("outputDir", "o", InputOption::VALUE_REQUIRED, "Output directory");
    }



    /**
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $optionName = "outputDir";
        if (!($outputDir = $input->getOption($optionName))) {
            throw new RuntimeException(sprintf('The "--%s" option requires a value.', $optionName));
        }
        $dir = getcwd();
        $script = $dir . (!Strings::endsWith($dir, "bin") ? "/bin" : "") . "/console";

        $enableCommands = [
            "sudo mv " . rtrim($outputDir, "/") . "/rabbitmq-consumer-* /etc/systemd/system/",
            "sudo systemctl daemon-reload"
        ];

        $disableCommands = [];

        foreach ($this->rabbitMQ->getConsumerServicesParameters() as $name => $params) {

            $serviceName = "rabbitmq-consumer-" . $name;
            $outputPath = rtrim($outputDir, "/") . "/" . $serviceName . ".service";

            $service = <<<TEXT
[Unit]
Description=RabbitMQ Consumer Service ($name)
After=network.target

[Service]
Type=simple
User=nginx
ExecStart=$script $params $name
Restart=on-abort


[Install]
WantedBy=multi-user.target
TEXT;
            file_put_contents($outputPath, $service);
            $output->writeln("Generated service file: $outputPath");
            $enableCommands[] = "sudo systemctl enable $serviceName";
            $disableCommands[] = "sudo systemctl disable $serviceName";
        }

        $enableCommands = implode("\n", $enableCommands);
        $disableCommands = implode("\n", $disableCommands);
        $output->write(<<<TEXT

To enable services start at boot:

$enableCommands

To disable services start at boot:

$disableCommands



TEXT
        );

        return 0;
    }

}
