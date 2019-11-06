<?php
declare(strict_types=1);

namespace Icarus\RabbitMQ\Messages;


use Nette\Utils\Json;
use Nette\Utils\Strings;


abstract class JsonMessage
{

    private $msgId;



    public function getMsgId()
    {
        return $this->msgId;
    }



    public function setMsgId($msgId): void
    {
        $this->msgId = $msgId;
    }



    public function getType(): string
    {
        return get_class($this);
    }



    private function getValues(): array
    {
        $reflection = new \ReflectionClass(static::class);
        $properties = $reflection->getProperties();
        $values = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            $values[$name] = $this->{"get" . Strings::firstUpper($name)}();
        }
        return $values;
    }



    public function __toString()
    {
        $arr = ['type' => static::class, 'msgId' => $this->getMsgId()] + $this->getValues();
        return Json::encode($arr);
    }



    public static function fromJson(string $json): JsonMessage
    {
        $arr = Json::decode($json, Json::FORCE_ARRAY);
        $class = $arr['type'];

        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Class '$class' not found.");
        }

        unset($arr['type']);
        $reflection = new \ReflectionClass($class);
        $constructorParameters = $reflection->getConstructor()->getParameters();
        $parameters = [];
        foreach ($constructorParameters as $parameter) {
            $name = $parameter->getName();

            if (!isset($arr[$name])) {
                throw new \InvalidArgumentException("Constructor parameters $name not found in the message.");
            }
            $value = $arr[$name];

            if ($parameter->getType()) {
                switch ($parameter->getType()->getName()) {
                    case "int":
                        $value = (int)$value;
                        break;
                    case "float":
                        $value = (float)$value;
                        break;
                }
            }

            $parameters[] = $value;
        }

        $messageObject = new $class(...$parameters);
        $messageObject->setMsgId($arr['msgId']);
        return $messageObject;
    }
}