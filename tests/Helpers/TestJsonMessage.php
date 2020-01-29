<?php
declare(strict_types=1);

namespace IcarusTests\RabbitMQ;


use Icarus\RabbitMQ\Messages\JsonMessage;


class TestJsonMessage extends JsonMessage
{

    /**
     * @var string
     */
    private $text;

    /**
     * @var int
     */
    private $number;

    /**
     * @var float
     */
    private $anotherNumber;

    private $mixedType;

    /**
     * @var \DateTime
     */
    private $datetime;



    public function __construct(string $text, int $number, float $anotherNumber, $mixedType, \DateTime $datetime)
    {
        $this->text = $text;
        $this->number = $number;
        $this->anotherNumber = $anotherNumber;
        $this->mixedType = $mixedType;
        $this->datetime = $datetime;
    }



    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }



    /**
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }



    /**
     * @return float
     */
    public function getAnotherNumber(): float
    {
        return $this->anotherNumber;
    }



    /**
     * @return mixed
     */
    public function getMixedType()
    {
        return $this->mixedType;
    }



    /**
     * @return \DateTime
     */
    public function getDatetime(): \DateTime
    {
        return $this->datetime;
    }

}