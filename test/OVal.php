<?php

declare(strict_types = 1);

namespace CodeTool\Jaeger\Tests\MongoDb;

class OVal
{
    public function __construct(...$kv)
    {
        for ($i = 0; $i <= (int)(\count($kv) / 2) - 1; $i++) {
            $this->{current($kv)} = next($kv);
            next($kv);
        }
    }
}
