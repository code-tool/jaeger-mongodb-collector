<?php

declare(strict_types = 1);

namespace CodeTool\Jaeger\Tests\MongoDb;

use CodeTool\Jaeger\MongoDb\JaegerMongoDbCommandConvertor;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;

final class CommandToStringConvertorTest extends TestCase
{
    public function dataProvider(): array
    {
        return [
            [
                ov('query', ov('player_id', 34)),
                'db.runCommand({query: {player_id: ?}})'
            ],
            [
                ov('query', ov('player_id', '34')),
                'db.runCommand({query: {player_id: "?"}})'
            ],
            [
                ov('query', ov('player_id', ov('$in', ['34', '343']))),
                'db.runCommand({query: {player_id: {$in: [\'...\']}}})'
            ],
            [
                ov(
                    'query',
                    ov(
                        'c',
                        ov('$gte', new UTCDateTime(1000000), '$lte', '$now')
                    )
                ),
                'db.runCommand({query: {c: {$gte: new UTCDateTime(?), $lte: "$now"}}})'
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @param object $command
     * @param string $expected
     */
    public function testConvert(object $command, string $expected): void
    {
        $convertor = new JaegerMongoDbCommandConvertor();

        $this->assertEquals($expected, $convertor->convert($command));
    }
}
