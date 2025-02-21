<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis;

use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\Node as LaudisNode;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\ResultSetInterface;

class ResultSet implements ResultSetInterface
{
    protected $parsedResults = [];

    public function __construct(protected $rawResult, protected $addLabels = false)
    {
        $this->parse();
    }

    public function valid(): bool
    {
        return true;
    }

    protected function parseNode(LaudisNode $node): array
    {
        $properties = $node->getProperties()->toArray();

        // If there are any ArrayList in the properties.
        // Covert it to an array.
        $properties = array_map(function ($element) {
            if ($element instanceof CypherList) {
                return $element->toArray();
            }

            return $element;
        }, $properties);

        return [
            'labels'     => $node->getLabels()->toArray(),
            'properties' => array_merge(
                ['id' => $node->getId()],
                $properties,
            ),
        ];
    }

    protected function parseRawResults($rawResults, $arrayWrap = true): array
    {
        $rawResults = is_array($rawResults) || !$arrayWrap ? $rawResults : [$rawResults];
        $properties = [];
        foreach ($rawResults as $rawKey => $value) {
            $key = $rawKey;

            if (str_contains((string) $rawKey, 'id(') && str_contains((string) $rawKey, ')')) {
                $key = 'id';
            }

            if (str_contains((string) $rawKey, '.')) {
                $keyExploded = explode('.', (string) $rawKey);
                $key = $keyExploded[1];
            }

            $properties[$key] = $value;
        }

        return $properties;
    }

    protected function parseItem($row): array
    {
        if ($row instanceof CypherMap) {
            $row = $row->values()[0];
        }

        if ($row instanceof LaudisNode) {
            return $this->parseNode($row);
        }

        return $this->parseRawResults($row);
    }

    protected function parseItems($row): array
    {
        $items = [];
        foreach ($row as $key => $value) {
            $items[$key] = $this->parseItem($value);
        }

        return $items;
    }

    protected function parse()
    {
        /** @var \Laudis\Neo4j\Types\CypherMap $results */
        $results = $this->rawResult->getResults();

        foreach ($results as $row) {
            if (!$row->first()->getValue() instanceof LaudisNode) {
                $this->parsedResults[] = $this->parseRawResults($row, false);
                continue;
            }

            if (count($row) > 1) {
                $this->parsedResults[] = $this->parseItems($row);
            } else {
                $this->parsedResults[] = $this->parseItem($row);
            }
        }

        return $this->parsedResults;
    }

    public function getResults()
    {
        return $this->parsedResults;
    }

    public function getRawResults()
    {
        return $this->rawResult;
    }

    public function offsetExists($key)
    {
        $results = $this->getResults();

        return isset($results[$key]);
    }

    public function offsetGet($key)
    {
        $results = $this->getResults();

        return $results[$key];
    }
}
