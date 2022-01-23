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
    protected $direction;

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
        $cypher = "MATCH (a), (b)
            WHERE id(a) = \$start
            AND id(b) = \$end
            CREATE (a)-[r:{$this->type} {";

        foreach($this->properties as $property => $value) {
            $cypher .= $property . ': $' . $property;
            $cypher .= ', ';
        }
        $cypher = mb_substr($cypher, 0, -2);
        $cypher .= '}]->(b) RETURN id(r)';
        RETURN $cypher;
    }

    protected function compileUpdateRelationship(): string
    {
        echo('compileUpdateRelationship');
        return '';
    }

    protected function compileDeleteRelationship(): string
    {
        return "MATCH (a)-[r:{$this->type}]->(b)
            WHERE id(a) = \$start
            AND id(b) = \$end
            DELETE r";
    }

    protected function compileGetRelationships(): string
    {
        if ($this->direction === 'out') {
            return "MATCH (a)-[r:{$this->type}]->(b)
            WHERE id(a) = \$start
            AND id(b) = \$end
            RETURN r";
        }

        if ($this->direction === 'in') {
            return "MATCH (a)<-[r:{$this->type}]-(b)
            WHERE id(a) = \$start
            AND id(b) = \$end
            RETURN r";
        }

        return "MATCH (a)-[r:{$this->type}]-(b)
            WHERE id(a) = \$start
            AND id(b) = \$end
            RETURN r";
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

        $properties = array_merge(
            $properties,
            $this->properties,
        );

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

    public function getAll()
    {
        $cypher = $this->compileGetRelationships();
        $properties = [
            'start' => $this->start->getId(),
            'end' => $this->end->getId(),
        ];
        $statement = new Statement($cypher, $properties);
        /** @var \Laudis\Neo4j\Databags\SummarizedResult $response */
        $response = $this->client->runStatement($statement);

        $relations = [];
        foreach($response as $items) {
            foreach ($items as $item) {
                $relation = new Relation($this->client);
                $relation->setProperties($item->getProperties()->toArray());
                $relation->setId($item->getId());
                $relation->setStartNode($this->start);
                $relation->setEndNode($this->end);
                $relation->setDirection($this->direction);
                $relation->setType($this->type);
                $relations[] = $relation;
            }
        }
        return $relations;
    }
}
