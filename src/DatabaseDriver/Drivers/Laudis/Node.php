<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis;

use Laudis\Neo4j\Client;
use Laudis\Neo4j\Databags\Statement;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\NodeInterface;

class Node implements NodeInterface
{
    /**
     * @var Client
     */
    protected $client;
    protected $id;
    protected $properties = [];

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function setProperty($key, $value)
    {
        $this->properties[$key] = $value;
    }

    public function hasId(): bool
    {
        return ($this->id !== null);
    }

    protected function compileCreateNode(): string
    {
        $cypher = 'MERGE (n {';

        foreach ($this->properties as $property => $value) {
            // Avoid null values
            if ($value === null) {
                continue;
            }
            $cypher .= $property . ': $' . $property;
            $cypher .= ', ';
        }
        $cypher = mb_substr($cypher, 0, -2);
        $cypher .= '}) RETURN id(n)';

        return $cypher;
    }

    protected function runUpdateNode()
    {
        dump('runUpdateNode');
        // Properties to update

        // Properties to remove
        //
    }

    protected function runCreateNode()
    {
        $cypher = $this->compileCreateNode();
        $statement = new Statement($cypher, $this->properties);
        $this->id = $this->client->runStatement($statement)
            ->first()
            ->first()
            ->getValue();
    }

    public function save(): NodeInterface
    {
        if ($this->hasId()) {
            $this->runUpdateNode();

            return $this;
        }

        $this->runCreateNode();
        return $this;
    }

    public function setId($id): Node
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function addLabels($labels)
    {
        foreach ($labels as $label) {
            $cypher = "MATCH (n)
                WHERE id(n) = \$id
                SET n:$label
                RETURN n";

            $statement = new Statement($cypher, ['id' => $this->id]);
            $this->client->runStatement($statement);
        }

        return $this;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getRelationships(): array
    {
        return [];
    }

    public function findPathsTo()
    {
        dump('findPathsTo');
    }
}