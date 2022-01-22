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
                //
            }

            $properties[$key] = $value;
        }

        return $properties;
    }

    public function parse()
    {
        /** @var \Laudis\Neo4j\Types\CypherMap $list */
        foreach ($this->rawResult->getResults() as $list) {
            $nodes = [];
            foreach ($list as $key => $item) {
                if ($item instanceof LaudisNode) {
                    $nodes[$key] = $this->parseNode($item);
                } else {
                    $this->parsedResults = $this->parseRawResults($item);
                }
            }
            $this->parsedResults[] = $nodes;
        }
    }

    public function getResults()
    {
        return $this->parsedResults;
    }
}
