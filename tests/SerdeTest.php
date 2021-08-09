<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\AllFieldTypesReadonly;
use Crell\Serde\Records\CustomNames;
use Crell\Serde\Records\Point;
use PHPUnit\Framework\TestCase;

class SerdeTest extends TestCase
{
    protected function getSerde(): JsonSerde
    {
        return new JsonSerde(new Analyzer());
    }

    /**
     * @test
     * @dataProvider roundTripProvider
     */
    public function round_trip(object $subject, ?array $fields = null): void
    {
        $serde = $this->getSerde();
        $serialized = $serde->serialize($subject);

        $deserialized = $serde->deserialize($serialized, $subject::class);

        $fields ??= $this->getFields($subject::class);

        foreach ($fields as $field) {
            self::assertEquals($subject->$field, $deserialized->$field);
        }
    }

    /**
     * @test
     * @dataProvider roundTripProvider81
     * @requires PHP >= 8.1
     */
    public function round_trip_81(object $subject, ?array $fields = null): void
    {
        $serde = $this->getSerde();
        $serialized = $serde->serialize($subject);

        $deserialized = $serde->deserialize($serialized, $subject::class);

        $fields ??= $this->getFields($subject::class);

        foreach ($fields as $field) {
            self::assertEquals($subject->$field, $deserialized->$field);
        }

    }

    public function roundTripProvider(): iterable
    {
        yield Point::class => [
            'subject' => new Point(1, 2, 3),
        ];

        yield AllFieldTypes::class => [
            'subject' => new AllFieldTypes(
                anint: 1,
                string: 'beep',
                afloat: 5.5,
                bool: true,
                dateTimeImmutable: new \DateTimeImmutable('2021-08-06 15:48:25'),
                dateTime: new \DateTime('2021-08-06 15:48:25'),
                simpleArray: [1, 2, 3],
                assocArray: ['a' => 'A', 'b' => 'B'],
                simpleObject: new Point(1, 2, 3),
                untyped: 5,
//                resource: \fopen(__FILE__, 'rb'),
            ),
        ];
    }

    public function roundTripProvider81(): iterable
    {
        yield Point::class => [
            'subject' => new Point(1, 2, 3),
        ];

        yield AllFieldTypesReadonly::class => [
            'subject' => new AllFieldTypesReadonly(
                anint: 1,
                string: 'beep',
                afloat: 5.5,
                bool: true,
                dateTimeImmutable: new \DateTimeImmutable('2021-08-06 15:48:25'),
                dateTime: new \DateTime('2021-08-06 15:48:25'),
                simpleArray: [1, 2, 3],
                assocArray: ['a' => 'A', 'b' => 'B'],
                simpleObject: new Point(1, 2, 3),
            ),
        ];
    }

    /**
     * @test
     */
    public function changes(): void
    {
        $subject = new CustomNames(first: 'Larry', last: 'Garfield');

        $serde = $this->getSerde();
        $serialized = $serde->serialize($subject);

        $expectedJson = json_encode(['firstName' => 'Larry', 'lastName' => 'Garfield']);

        self::assertEquals($expectedJson, $serialized);

        $deserialized = $serde->deserialize($serialized, $subject::class);

        $fields ??= $this->getFields($subject::class);

        foreach ($fields as $field) {
            self::assertEquals($subject->$field, $deserialized->$field);
        }
    }

    protected function getFields(string $class): array
    {
        $analyzer = new Analyzer();
        // @todo Generalize this.
        $classDef = $analyzer->analyze($class, ClassDef::class);

        return array_map(static fn(Field $f) => $f->phpName, $classDef->properties);
    }

}
