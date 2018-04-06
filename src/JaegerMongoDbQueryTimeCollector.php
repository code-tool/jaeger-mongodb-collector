<?php

declare(strict_types=1);

namespace CodeTool\Jaeger\MongoDb;

use Jaeger\Log\ErrorLog;
use Jaeger\Span\SpanInterface;
use Jaeger\Tag\BoolTag;
use Jaeger\Tag\ComponentTag;
use Jaeger\Tag\DbInstanceTag;
use Jaeger\Tag\DbStatementTag;
use Jaeger\Tag\DbType;
use Jaeger\Tag\PeerHostnameTag;
use Jaeger\Tag\PeerPortTag;
use Jaeger\Tag\SpanKindClientTag;
use Jaeger\Tracer\TracerInterface;
use MongoDB\Driver\Monitoring\CommandFailedEvent;
use MongoDB\Driver\Monitoring\CommandStartedEvent;
use MongoDB\Driver\Monitoring\CommandSubscriber;
use MongoDB\Driver\Monitoring\CommandSucceededEvent;

class JaegerMongoDbQueryTimeCollector implements CommandSubscriber
{
    private $tracer;

    /**
     * @var SpanInterface[]
     */
    private $requestIdToSpan = [];

    /**
     * @var CommandToStringConvertor
     */
    private $convertor;

    public function __construct(TracerInterface $tracer, CommandToStringConvertor $convertor)
    {
        $this->tracer = $tracer;
        $this->convertor = $convertor;
    }

    public function commandStarted(CommandStartedEvent $event)
    {
        /** @var MongoDB\Driver\Server $server */
        $server = $event->getServer();

        $this->requestIdToSpan[$event->getRequestId()] = $this->tracer->start(
            sprintf('mongodb.%s', $event->getCommandName()),
            [
                new SpanKindClientTag(),
                new ComponentTag('php-mongodb'),

                new DbType('mongo'),
                new DbInstanceTag($event->getDatabaseName()),
                new DbStatementTag($this->convertor->convert($event->getCommand())),

                new PeerHostnameTag($server->getHost()),
                new PeerPortTag($server->getPort()),
            ]
        );
    }

    private function getSpanByEvent($event): ?SpanInterface
    {
        if (false === array_key_exists($event->getRequestId(), $this->requestIdToSpan)) {
            // warning, should not happen!
            return null;
        }

        $span = $this->requestIdToSpan[$event->getRequestId()];
        unset($this->requestIdToSpan[$event->getRequestId()]);

        return $span;
    }

    public function commandFailed(CommandFailedEvent $event)
    {
        if (null === $span = $this->getSpanByEvent($event)) {
            return;
        }

        $span->addTag(new BoolTag('error', true));
        $span->addLog(new ErrorLog($event->getError()->getMessage(), $event->getError()->getTraceAsString()));

        $this->tracer->finish($span, $event->getDurationMicros());
    }

    public function commandSucceeded(CommandSucceededEvent $event)
    {
        if (null === $span = $this->getSpanByEvent($event)) {
            return;
        }

        $this->tracer->finish($span, $event->getDurationMicros());
    }
}
