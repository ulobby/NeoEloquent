<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis;

use Laudis\Neo4j\Client;
use Laudis\Neo4j\Databags\Statement;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\NodeInterface;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\RelationInterface;

class Relation implements RelationInterface
{
    /**
     * @var Client
     */
    protected $client;
    protected $id;
    protected $properties = [];
    protected $type;
    protected $start;
    protected $end;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function hasId(): bool
    {
        return ($this->id !== null);
    }

    protected function compileCreateRelationship(): string
    {
        return "MATCH (a), (b)
            WHERE id(a) = \$start
            AND id(b) = \$end
            CREATE (a)-[r:{$this->type}]->(b)
            RETURN id(r)";
    }

    protected function compileUpdateRelationship(): string
    {
        dump('compileUpdateRelationship');
        return '';
    }

    protected function compileDeleteRelationship(): string
    {
        return "MATCH (a)-[r:{$this->type}]->(b)
            WHERE id(a) = \$start
            AND id(b) = \$end
            DELETE r";
    }

    public function save()
    {
        if ($this->hasId()) {
            $cypher = $this->compileUpdateRelationship();
        } else {
            $cypher = $this->compileCreateRelationship();
        }

        $properties = [
            'start' => $this->start->getId(),
            'end' => $this->end->getId(),
        ];

//        $properties = array_merge(
//            $properties,
//            $this->properties,
//        );

        $statement = new Statement($cypher, $properties);

        $result = $this->client->runStatement($statement);
        $list = $result->first();
        $pair = $list->first();

        $this->id = $pair->getValue();
        return $this;
    }

    public function delete()
    {
        $cypher = $this->compileDeleteRelationship();
        $properties = [
            'start' => $this->start->getId(),
            'end' => $this->end->getId(),
        ];
        $statement = new Statement($cypher, $properties);
        $this->client->runStatement($statement);
        return $this;
    }

    public function setType($type): Relation
    {
        $this->type = $type;
        return $this;
    }

    public function setStartNode($start): Relation
    {
        $this->start = $start;
        return $this;
    }

    public function getStartNode(): Node
    {
        return $this->start;
    }

    public function setEndNode($end): Relation
    {
        $this->end = $end;
        return $this;
    }

    public function getEndNode(): Node
    {
        return $this->end;
    }

    public function setProperties($properties): Relation
    {
        $this->properties = $properties;
        return $this;
    }

    public function setProperty($key, $value): Relation
    {
        $this->properties[$key] = $value;
        return $this;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getId()
    {
        return $this->id;
    }
}