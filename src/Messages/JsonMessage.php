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
            $value = $this->getValue($property);
            $values[$name] = $value;
        }
        return $values;
    }



    private function getValue(\ReflectionProperty $property)
    {
        $prefixes = ['get', 'is'];
        $suffix = Strings::firstUpper($property->getName());
        foreach ($prefixes as $prefix) {
            $methodName = $prefix . $suffix;
            if (!method_exists($this, $methodName)) {
                continue;
            }
            $value = $this->$methodName();
        }

        if (!isset($value)) {
            trigger_error("No getter for {$property->getName()} (both get$suffix and is$suffix do not exist", E_USER_ERROR);
            return null;
        }

        if ($value instanceof \DateTime) {
            $value = $value->getTimestamp();
        }

        return $value;
    }



    public function __toString()
    {
        $arr = ['type' => static::class] + $this->getValues();
        if ($this->getMsgId()) {
            $arr['id'] = $this->getMsgId();
        }
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
                switch ((string)$parameter->getType()) {
                    case "int":
                        $value = (int)$value;
                        break;
                    case "float":
                        $value = (float)$value;
                        break;
                    case "bool":
                        $value = (bool)$value;
                        break;
                    case "DateTime":
                        $value = \DateTime::createFromFormat("U", "$value");
                        break;
                }
            }

            $parameters[] = $value;
        }

        $messageObject = new $class(...$parameters);
        $messageObject->setMsgId($arr['id'] ?? "");
        return $messageObject;
    }
}