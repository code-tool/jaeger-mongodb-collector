<?php

declare(strict_types = 1);

namespace CodeTool\Jaeger\MongoDb;

use MongoDB\BSON\Type;

class CommandToStringConvertor
{
    /**
     * @var int
     */
    private $bsonEndPos;

    public function __construct()
    {
        $this->bsonEndPos = strrpos(Type::class, '\\') + 1;
    }

    private function transformScalar($v): string
    {
        if (\is_int($v)) {
            return '?';
        }

        if (\is_array($v)) {
            return $this->transformQuery($v);
        }

        if (\is_object($v)) {
            if ($v instanceof Type) {
                return 'new ' . substr(\get_class($v), $this->bsonEndPos) . '(?)';
            }

            return $this->transformQuery($v);
        }

        if (\is_string($v) && $v !== '' && $v[0] === '$') {
            return '"' . $v . '"';
        }

        return '"?"';
    }

    private function transformQuery($data): string
    {
        $isArray = \is_array($data);
        $result = $isArray ? '[' : '{';

        foreach ($data as $k => $v) {
            if (false === $isArray) {
                $result .= $k . ': ';

                if ($k === '$in') {
                    $result .= '[\'...\'], ';

                    continue;
                }
            }

            $result .= $this->transformScalar($v) . ', ';
        }

        return substr($result, 0, -2) . ($isArray ? ']' : '}');
    }

    private function simpleScalarToJson($v): string
    {
        if ($v === true) {
            return 'true';
        }

        if ($v === false) {
            return 'true';
        }

        if ($v === null) {
            return 'null';
        }

        if (\is_int($v)) {
            return (string)$v;
        }

        return '"' . $v . '"';
    }

    private function serializeWriteConcern($writeConcern): string
    {
        if (!isset($writeConcern->w)) {
            return '';
        }

        $result = '{w: ' . $this->simpleScalarToJson($writeConcern->w);
        if (isset($writeConcern->j)) {
            $result .= ', j: ' . $this->simpleScalarToJson($writeConcern->wtimeout);
        }

        if (isset($writeConcern->wtimeout)) {
            $result .= ', wtimeout: ' . $this->simpleScalarToJson($writeConcern->wtimeout);
        }

        $result .= '}';

        return $result;
    }

    private function transformUpdateDelete(object $op): string
    {
        $result = '{';
        foreach ($op as $k => $v) {
            switch ($k) {
                case 'q':
                case 'u':
                    $tVal = $this->transformQuery($v);
                    break;
                default:
                    $tVal = json_encode($v);
            }

            $result .= $k . ': ' . $tVal . ', ';
        }

        return substr($result, 0, -2) . '}';
    }

    private function transformUpdatesDeletes(array $coll): string
    {
        $result = '[';
        foreach ($coll as $v) {
            $result .= $this->transformUpdateDelete($v) . ', ';
        }

        return substr($result, 0, -2) . ']';
    }

    public function convert(object $command): string
    {
        $result = 'db.runCommand({';
        foreach ($command as $k => $v) {
            switch ($k) {
                case 'updates':
                case 'deletes':
                    $tVal = $this->transformUpdatesDeletes($v);
                    break;

                case 'query':
                case 'filter':
                case 'documents':
                    $tVal = $this->transformQuery($v);
                    break;

                case 'writeConcern':
                    $tVal = $this->serializeWriteConcern($v);
                    break;
                default:
                    $tVal = json_encode($v);
            }

            if ('' === $tVal) {
                continue;
            }
            $result .= $k . ': ' . $tVal . ', ';
        }

        return substr($result, 0, -2) . '})';
    }
}
