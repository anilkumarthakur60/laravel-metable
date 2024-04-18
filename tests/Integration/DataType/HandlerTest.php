<?php

namespace Plank\Metable\Tests\Integration\DataType;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Plank\Metable\DataType\ArrayHandler;
use Plank\Metable\DataType\BooleanHandler;
use Plank\Metable\DataType\DateTimeHandler;
use Plank\Metable\DataType\FloatHandler;
use Plank\Metable\DataType\HandlerInterface;
use Plank\Metable\DataType\IntegerHandler;
use Plank\Metable\DataType\ModelCollectionHandler;
use Plank\Metable\DataType\ModelHandler;
use Plank\Metable\DataType\NullHandler;
use Plank\Metable\DataType\ObjectHandler;
use Plank\Metable\DataType\SerializableHandler;
use Plank\Metable\DataType\SerializeHandler;
use Plank\Metable\DataType\StringHandler;
use Plank\Metable\Tests\Mocks\SampleMetable;
use Plank\Metable\Tests\Mocks\SampleSerializable;
use Plank\Metable\Tests\TestCase;
use stdClass;

class HandlerTest extends TestCase
{
    private static $resource;
    public static function handlerProvider(): array
    {
        $dateString = '2017-01-01 00:00:00.000000+0000';
        $datetime = Carbon::createFromFormat('Y-m-d H:i:s.uO', $dateString);
        $timestamp = $datetime->getTimestamp();

        $object = new stdClass();
        $object->foo = 'bar';
        $object->baz = 3;

        $model = new SampleMetable();

        self::$resource = fopen('php://memory', 'r');

        return [
            'array' => [
                'handler' => new ArrayHandler(),
                'type' => 'array',
                'value' => ['foo' => ['bar'], 'baz'],
                'invalid' => [new stdClass()],
                'numericValue' => null,
                'stringValue' => null,
                'stringValueComplex' => json_encode(['foo' => ['bar'], 'baz']),
                'isIdempotent' => true,
            ],
            'boolean' => [
                'handler' => new BooleanHandler(),
                'type' => 'boolean',
                'value' => true,
                'invalid' => [1, 0, '', [], null],
                'numericValue' => 1,
                'stringValue' => 'true',
                'stringValueComplex' => 'true',
                'isIdempotent' => true,
            ],
            'datetime' => [
                'handler' => new DateTimeHandler(),
                'type' => 'datetime',
                'value' => $datetime,
                'invalid' => [2017, '2017-01-01'],
                'numericValue' => $timestamp,
                'stringValue' => $dateString,
                'stringValueComplex' => $dateString,
                'isIdempotent' => true,
            ],
            'float' => [
                'handler' => new FloatHandler(),
                'type' => 'float',
                'value' => 1.1,
                'invalid' => ['1.1', 1],
                'numericValue' => 1.1,
                'stringValue' => '1.1',
                'stringValueComplex' => '1.1',
                'isIdempotent' => true,
            ],
            'integer' => [
                'handler' => new IntegerHandler(),
                'type' => 'integer',
                'value' => 3,
                'invalid' => [1.1, '1'],
                'numericValue' => 3,
                'stringValue' => '3',
                'stringValueComplex' => '3',
                'isIdempotent' => true,
            ],
            'model' => [
                'handler' => new ModelHandler(),
                'type' => 'model',
                'value' => $model,
                'invalid' => [new stdClass()],
                'numericValue' => null,
                'stringValue' => SampleMetable::class,
                'stringValueComplex' => SampleMetable::class,
                'isIdempotent' => true,
            ],
            'model collection' => [
                'handler' => new ModelCollectionHandler(),
                'type' => 'collection',
                'value' => new Collection([new SampleMetable()]),
                'invalid' => [collect()],
                'numericValue' => null,
                'stringValue' => null,
                'stringValueComplex' => null,
                'isIdempotent' => true,
            ],
            'null' => [
                'handler' => new NullHandler(),
                'type' => 'null',
                'value' => null,
                'invalid' => [0, '', 'null', [], false],
                'numericValue' => null,
                'stringValue' => null,
                'stringValueComplex' => null,
                'isIdempotent' => true,
            ],
            'object' => [
                'handler' => new ObjectHandler(),
                'type' => 'object',
                'value' => $object,
                'invalid' => [[]],
                'numericValue' => null,
                'stringValue' => null,
                'stringValueComplex' => json_encode($object),
                'isIdempotent' => true,
            ],
            'serialize' => [
                'handler' => new SerializeHandler(),
                'type' => 'serialized',
                'value' => ['foo' => 'bar', 'baz' => [3]],
                'invalid' => [self::$resource],
                'numericValue' => null,
                'stringValue' => null,
                'stringValueComplex' => serialize(['foo' => 'bar', 'baz' => [3]]),
                'isIdempotent' => false,
            ],
            'serializable' => [
                'handler' => new SerializableHandler(),
                'type' => 'serializable',
                'value' => new SampleSerializable(['foo' => 'bar']),
                'invalid' => [],
                'numericValue' => null,
                'stringValue' => null,
                'stringValueComplex' => serialize(new SampleSerializable(['foo' => 'bar'])),
                'isIdempotent' => true,
            ],
            'string' => [
                'handler' => new StringHandler(),
                'type' => 'string',
                'value' => 'foo',
                'invalid' => [1, 1.1],
                'numericValue' => null,
                'stringValue' => 'foo',
                'stringValueComplex' => 'foo',
                'isIdempotent' => true,
            ],
            'long-string' => [
                'handler' => new StringHandler(),
                'type' => 'string',
                'value' => str_repeat('a', 300),
                'invalid' => [1, 1.1],
                'numericValue' => null,
                'stringValue' => str_repeat('a', 255),
                'stringValueComplex' => str_repeat('a', 255),
                'isIdempotent' => true,
            ],
            'numeric-string' => [
                'handler' => new StringHandler(),
                'type' => 'string',
                'value' => '1.2345',
                'invalid' => [1, 1.1],
                'numericValue' => 1.2345,
                'stringValue' => '1.2345',
                'stringValueComplex' => '1.2345',
                'isIdempotent' => true,
            ],
        ];
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$resource) {
            fclose(self::$resource);
            self::$resource = null;
        }
        parent::tearDownAfterClass();
    }

    /**
     * @dataProvider handlerProvider
     */
    public function test_it_can_verify_and_serialize_data(
        HandlerInterface $handler,
        string $type,
        mixed $value,
        array $incompatible,
        null|int|float $numericValue,
        null|string $stringValue,
        null|string $stringValueComplex,
        bool $isIdempotent
    ): void {
        $this->assertEquals($type, $handler->getDataType());
        $this->assertTrue($handler->canHandleValue($value));

        foreach ($incompatible as $incompatibleValue) {
            $this->assertFalse($handler->canHandleValue($incompatibleValue));
        }

        $serialized = $handler->serializeValue($value);
        $unserialized = $handler->unserializeValue($serialized);

        $this->assertEquals($value, $unserialized);
        $this->assertEquals($numericValue, $handler->getNumericValue($value));
        config()->set('metable.indexComplexDataTypes', false);
        $this->assertEquals($stringValue, $handler->getStringValue($value));
        config()->set('metable.indexComplexDataTypes', true);
        $this->assertEquals($stringValueComplex, $handler->getStringValue($value));

        $this->assertEquals($isIdempotent, $handler->isIdempotent());
    }
}
