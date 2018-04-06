<?php

namespace CodeTool\Jaeger\Tests\MongoDb;

function ov(...$kv): object
{
    $result = new \stdClass();

    for ($i = 0; $i <= (int)(\count($kv) / 2) - 1; $i++) {
        $result->{current($kv)} = next($kv);
        next($kv);
    }

    return $result;
}
