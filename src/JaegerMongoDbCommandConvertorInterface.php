<?php

declare(strict_types = 1);

namespace CodeTool\Jaeger\MongoDb;

interface JaegerMongoDbCommandConvertorInterface
{
    public function convert(object $command): string;
}
