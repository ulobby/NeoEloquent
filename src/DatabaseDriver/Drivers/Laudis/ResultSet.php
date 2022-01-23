<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis;

use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\ResultSetInterface;
use Laudis\Neo4j\Types\Node as LaudisNode;

class ResultSet implements ResultSetInterface
{
    protected $rawResult;
    protected $parsedResults = [];

    public function __construct($rawResult)
    {
        $this->rawResult = $rawResult;
        $this->parse();
    }

    public function valid(): bool
    {
        return true;
    }

    protected function parseNode(LaudisNode $node): array
    {
        return array_merge(
            ['id' => $node->getId()],
            $node->getProperties()->toArray(),
        );
    }

    protected function parseRawResults($rawResults): array
    {
        $rawResults = is_array($rawResults) ? $rawResults : [$rawResults];
        $properties = [];
        foreach ($rawResults as $rawKey => $value) {
            $key = '';

            if (str_contains($rawKey, '(') && str_contains($rawKey, ')')) {
                $key = 'id';
            }

            if (str_contains($rawKey, '.')){
                $keyExploded = explode('.', $rawKey);
                $key = $keyExploded[1];
            }

            if ($key === '') {
                // Exception?
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
        foreach($row as $key => $value) {
            $items[$key] = $this->parseItem($value);
        }
        return $items;
    }

    public function parse()
    {
        /** @var \Laudis\Neo4j\Types\CypherMap $results */
        $results = $this->rawResult->getResults();
        foreach ($results as $row) {
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
}
