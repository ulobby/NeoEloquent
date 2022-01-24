<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis;

use Laudis\Neo4j\Client;
use Laudis\Neo4j\Databags\Statement;
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
    protected $direction;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function hasId(): bool
    {
        return ($this->id !== null);
    }

    protected function runStatement($cypher, $binding)
    {
        $statement = new Statement($cypher, $binding);
        return $this->client->runStatement($statement);
    }

    protected function compileCreateRelationship(): string
    {
        $cypher = "MATCH (a), (b)
            WHERE id(a) = {$this->start->getId()}
            AND id(b) = {$this->end->getId()}
            CREATE (a)-[r:{$this->type} {";

        foreach($this->properties as $property => $value) {
            $cypher .= $property . ': $' . $property;
            $cypher .= ', ';
        }

        $cypher = mb_substr($cypher, 0, -2);
        $cypher .= '}]->(b) RETURN id(r)';
        RETURN $cypher;
    }

    protected function compileNodeRelation(): string
    {
        if ($this->direction === 'out') {
            return "(a)-[r:$this->type]->(b)";
        }

        if ($this->direction === 'in') {
            return "(a)<-[r:$this->type]-(b)";
        }

        return "(a)-[r:$this->type]-(b)";
    }

    protected function compileUpdateProperties(): string
    {
        $cypher = "MATCH {$this->compileNodeRelation()}
            WHERE id(a) = {$this->start->getId()}
            AND id(b) = {$this->end->getId()}
            AND id(r) = $this->id
            SET ";

        foreach($this->properties as $property => $value) {
            $cypher .= 'r.' . $property . ' = $' . $property;
            $cypher .= ', ';
        }

        $cypher = mb_substr($cypher, 0, -2);
        $cypher .= ' RETURN r';
        RETURN $cypher;
    }

    protected function compileDeleteRelationship(): string
    {
        return "MATCH ()-[r:{$this->type}]-()
            WHERE id(r) = {$this->getId()}
            DELETE r";
    }

    protected function compileGetRelationships(): string
    {
        $withEnd = '';

        if ($this->end !== null) {
            $withEnd = "AND id(b) = {$this->end->getId()}";
        }

        return "MATCH {$this->compileNodeRelation()}
            WHERE id(a) = {$this->start->getId()}
            $withEnd
            RETURN a, b, r";
    }

    protected function runUpdateRelationship()
    {
        // 1. Remove null properties (TODO).

        // 2. Update attributes.
        $cypher = $this->compileUpdateProperties();
        $propertiesWithoutNull = array_filter($this->properties);
        $this->runStatement($cypher, $propertiesWithoutNull);
    }

    protected function runCreateRelationship()
    {
        $cypher = $this->compileCreateRelationship();
        $result = $this->runStatement($cypher, $this->properties);
        $list = $result->first();
        $pair = $list->first();
        $this->id = $pair->getValue();
    }

    public function save()
    {
        if ($this->hasId()) {
            $this->runUpdateRelationship();

            return $this;
        }

        $this->runCreateRelationship();

        return $this;
    }

    public function delete()
    {
        $cypher = $this->compileDeleteRelationship();
        $this->runStatement($cypher, []);
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

    public function getType()
    {
        return $this->type;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setDirection($direction): Relation
    {
        $this->direction = $direction;
        return $this;
    }

    protected function parseRelation($items): Relation
    {
        $start = new Node($this->client);
        $end = new Node($this->client);
        $relation = new Relation($this->client);
        foreach ($items as $key => $item) {
            // Start node
            if ($key === 'a') {
                $start->setProperties($item->getProperties()->toArray())
                    ->setId($item->getId());
            }
            // End node
            if ($key === 'b') {
                $end->setProperties($item->getProperties()->toArray())
                    ->setId($item->getId());
            }
            // Relation
            if ($key === 'r') {
                $relation->setProperties($item->getProperties()->toArray())
                    ->setId($item->getId());
            }
        }

        if ($this->direction === 'in') {
            $relation->setStartNode($end)
                ->setEndNode($start);
        } else {
            $relation->setStartNode($start)
                ->setEndNode($end);
        }

        $relation
            ->setDirection($this->direction)
            ->setType($this->type);

        return $relation;
    }

    public function getAll(): array
    {
        $cypher = $this->compileGetRelationships();
        $response = $this->runStatement($cypher, []);

        $relations = [];
        foreach($response as $items) {
            $relations[] = $this->parseRelation($items);
        }

        return $relations;
    }
}
