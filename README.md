# mongodb-jaeger-collector
MongoDB's driver CommandSubscriber interface implementation for Jaeger

How to use
```php
\MongoDB\Driver\Monitoring\addSubscriber(
    new \CodeTool\Jaeger\MongoDb\JaegerMongoDbQueryTimeCollector(
        $tracer,
        new \CodeTool\Jaeger\MongoDb\JaegerMongoDbCommandConvertor()
    )
);
```
