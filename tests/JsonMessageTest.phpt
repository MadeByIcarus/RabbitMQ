<?php
declare(strict_types=1);

namespace IcarusTests\RabbitMQ;


require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Helpers/TestJsonMessage.php';
require_once __DIR__ . '/Helpers/TestInvalidJsonMessage.php';


use Icarus\RabbitMQ\Messages\JsonMessage;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tester\Assert;
use Tester\TestCase;


class JsonMessageTest extends TestCase
{

    public function testMessageToJson()
    {
        $timestamp = 1546300800;

        $message = new TestJsonMessage(
            "ahoj",
            1,
            2.3,
            "some",
            \DateTime::createFromFormat("U", "$timestamp"),
            true
        );

        $json = (string)$message;

        $expectedJson = Json::encode([
            'type' => TestJsonMessage::class,
            'text' => "ahoj",
            'number' => 1,
            'anotherNumber' => 2.3,
            'mixedType' => "some",
            'datetime' => $timestamp,
            'okay' => true
        ]);

        Assert::equal($expectedJson, $json);
    }



    public function getJsonTestValues()
    {
        return [
            ["ahoj", 1, 2.3, "some", 1546300800, true]
        ];
    }



    /**
     * @dataProvider getJsonTestValues
     */
    public function testJsonToMessage($text, $number, $anotherNumber, $mixedType, $timestamp, $okay)
    {
        $json = Json::encode([
            'type' => TestJsonMessage::class,
            'text' => $text,
            'number' => $number,
            'anotherNumber' => $anotherNumber,
            'mixedType' => $mixedType,
            'datetime' => $timestamp,
            'okay' => $okay
        ]);

        /** @var TestJsonMessage $message */
        $message = JsonMessage::fromJson($json);

        Assert::type(TestJsonMessage::class, $message);
        Assert::same($text, $message->getText());
        Assert::same($number, $message->getNumber());
        Assert::same($anotherNumber, $message->getAnotherNumber());
        Assert::same($mixedType, $message->getMixedType());
        Assert::equal($timestamp, $message->getDatetime()->getTimestamp());
        Assert::equal($okay, $message->isOkay());
    }



    public function testInvalidJson()
    {
        Assert::exception(function () {
            JsonMessage::fromJson("asdfdasfsdf");
        }, JsonException::class);

        Assert::exception(function () {
            $json = Json::encode([
                'type' => TestJsonMessage::class,
                'text' => "DS",
                'number' => 4,
                'anotherNumber' => 4,
                "mixedType2" => "SD" // invalid parameter name..
            ]);
            JsonMessage::fromJson($json);
        }, \InvalidArgumentException::class);

        Assert::exception(function () {
            $json = Json::encode([
                'type' => "SomeClassName", // does not exist
                'text' => "DS"
            ]);
            JsonMessage::fromJson($json);
        }, \InvalidArgumentException::class, "Class 'SomeClassName' not found.");
    }



    public function testMissingGetterMethod()
    {
        Assert::error(
            function () {
                $message = new TestInvalidJsonMessage("a");
                (string)$message;
            },
            E_USER_ERROR,
            "#No getter#"
        );
    }
}

(new JsonMessageTest())->run();