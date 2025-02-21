<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis;

use Laudis\Neo4j\Client;
use Laudis\Neo4j\Databags\Statement;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\RelationInterface;

class Relation implements RelationInterface
{
    protected $id;
    protected $properties = [];
    protected $type;
    protected $start;
    protected $end;
    protected $direction;

    /**
     * @param Client $client
     */
    public function __construct(protected $client)
    {
    }

    public function hasId(): bool
    {
        return $this->id !== null;
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
            CREATE (a)-[r:`$this->type` ";

        if (!empty($this->properties)) {
            $cypher .= ' { ';
            foreach ($this->properties as $property => $value) {
                $cypher .= $property.': $'.$property;
                $cypher .= ', ';
            }

            $cypher = mb_substr($cypher, 0, -2);
            $cypher .= ' } ';
        }

        $cypher .= ']->(b) RETURN id(r)';

        return $cypher;
    }

    protected function compileNodeRelation(): string
    {
        if ($this->direction === 'out') {
            return "(a)-[r:`$this->type`]->(b)";
        }

        if ($this->direction === 'in') {
            return "(a)<-[r:`$this->type`]-(b)";
        }

        return "(a)-[r:`$this->type`]-(b)";
    }

    protected function compileUpdateProperties(array $propertiesNotNull): string
    {
        $cypher = "MATCH {$this->compileNodeRelation()}
            WHERE id(a) = {$this->start->getId()}
            AND id(b) = {$this->end->getId()}
            AND id(r) = $this->id
            SET ";

        foreach ($propertiesNotNull as $property => $value) {
            $cypher .= 'r.'.$property.' = $'.$property;
            $cypher .= ', ';
        }

        $cypher = mb_substr($cypher, 0, -2);
        $cypher .= ' RETURN r';

        return $cypher;
    }

    protected function compileDeleteRelationship(): string
    {
        return "MATCH ()-[r:`$this->type`]-()
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

    protected function compileRemoveProperties(array $propertiesNull): string
    {
        $cypher = "MATCH {$this->compileNodeRelation()}
            WHERE id(a) = {$this->start->getId()}
            AND id(b) = {$this->end->getId()}
            AND id(r) = $this->id 
            REMOVE ";

        foreach ($propertiesNull as $property) {
            $cypher .= 'r.'.$property;
            $cypher .= ', ';
        }

        return mb_substr($cypher, 0, -2);
    }

    protected function runUpdateRelationship()
    {
        // 1. Remove null properties.
        $propertiesNull = array_keys($this->properties, null, true);
        if (!empty($propertiesNull)) {
            $cypher = $this->compileRemoveProperties($propertiesNull);
            $this->runStatement($cypher, []);
        }

        // 2. Update properties.
        $propertiesNotNull = array_filter($this->properties);
        if (!empty($propertiesNotNull)) {
            $cypher = $this->compileUpdateProperties($propertiesNotNull);
            $this->runStatement($cypher, $propertiesNotNull);
        }
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
        $aNode = new Node($this->client);
        $bNode = new Node($this->client);
        $relation = new Relation($this->client);
        $startNodeId = null;

        foreach ($items as $key => $item) {
            // Start node
            if ($key === 'a') {
                $aNode->setProperties($item->getProperties()->toArray())
                    ->setId($item->getId());
            }
            // End node
            if ($key === 'b') {
                $bNode->setProperties($item->getProperties()->toArray())
                    ->setId($item->getId());
            }
            // Relation
            if ($key === 'r') {
                $startNodeId = $item->getStartNodeId();
                $relation
                    ->setType($item->getType())
                    ->setProperties($item->getProperties()->toArray())
                    ->setId($item->getId());
            }
        }

        /**
         * If the developer explicitly chose a direction, then, we
         * should keep that direction.
         */
        if (($this->direction === 'in') || ($this->direction === 'out')) {
            if ($aNode->getId() === $startNodeId) {
                $relation->setStartNode($aNode)->setEndNode($bNode);
            } else {
                $relation->setStartNode($bNode)->setEndNode($aNode);
            }
            $relation->setDirection($this->direction);

            return $relation;
        }

        /**
         * In case the developer didn't choose any specific direction. Then,
         * it doesn't matter, just keep it coherent.
         */
        $relation->setStartNode($aNode)
            ->setEndNode($bNode);

        if ($aNode->getId() === $startNodeId) {
            $relation->setDirection('out');
        } else {
            $relation->setDirection('in');
        }

        return $relation;
    }

    public function getAll(): array
    {
        $cypher = $this->compileGetRelationships();
        $response = $this->runStatement($cypher, []);

        $relations = [];
        foreach ($response as $items) {
            $relations[] = $this->parseRelation($items);
        }

        return $relations;
    }
}
