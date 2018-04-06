<?php

declare(strict_types = 1);

namespace CodeTool\Jaeger\Tests\MongoDb;

use CodeTool\Jaeger\MongoDb\JaegerMongoDbCommandConvertor;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;

final class CommandToStringConvertorTest extends TestCase
{
    public function dataProvider()
    {
        return [
            [
                ['query' => ['player_id' => 34]],
                'db.runCommand({query: {player_id: ?}})'
            ],
            [
                ['query' => ['player_id' => '34']],
                'db.runCommand({query: {player_id: "?"}})'
            ],
            [
                ['query' => ['player_id' => ['$in' => ['34', '343']]]],
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

    private function array2object($array)
    {
        return json_decode(json_encode($array));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param array|\stdClass $command
     * @param string          $expected
     */
    public function testConvert($command, string $expected): void
    {
        $convertor = new JaegerMongoDbCommandConvertor();

        $data = $command;
        if (\is_array($command)) {
            $data = $this->array2object($command);
        }

        $this->assertEquals($expected, $convertor->convert($data));
    }
}
