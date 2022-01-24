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
    protected $labels;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function setProperties($properties)
    {
        $this->properties = $properties;

        return $this;
    }

    public function setProperty($key, $value)
    {
        $this->properties[$key] = $value;
    }

    public function hasId(): bool
    {
        return $this->id !== null;
    }

    protected function compileCreateNode(): string
    {
        $cypher = 'CREATE (n {';

        foreach ($this->properties as $property => $value) {
            // Avoid null values
            if ($value === null) {
                continue;
            }
            $cypher .= $property.': $'.$property;
            $cypher .= ', ';
        }
        $cypher = mb_substr($cypher, 0, -2);
        $cypher .= '}) RETURN id(n)';

        return $cypher;
    }

    protected function compileGetNode(): string
    {
        return 'MATCH (n) WHERE id(n) = $id RETURN n';
    }

    protected function compileGetLabels(): string
    {
        return 'MATCH (n) WHERE id(n) = $id RETURN labels(n)';
    }

    protected function compileDeleteNode(): string
    {
        return 'MATCH (n) WHERE id(n) = $id DETACH DELETE n';
    }

    protected function runUpdateNode()
    {
        echo ' runUpdateNode ';
        // Properties to update

        // Properties to remove
        //
    }

    public function populateNode()
    {
        $cypher = $this->compileGetNode();
        $statement = new Statement($cypher, ['id' => $this->id]);
        $response = $this->client->runStatement($statement);
        $resultSet = new ResultSet($response);
        $this->properties = $resultSet->getResults()[0];

        return $this;
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

    public function setId($id): NodeInterface
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

    public function getRelationships($type, $direction): array
    {
        $relation = new Relation($this->client);
        $relation->setStartNode($this);
        $relation->setType($type);
        $relation->setDirection($direction);

        return $relation->getAll();
    }

    protected function compileGetRelations()
    {
    }

    /**
     * @param NodeInterface $to
     * @param $type
     * @param $direction "all", "in", "out"
     *
     * @return array
     */
    public function findPathsTo(NodeInterface $to, $type = null, $direction = null): array
    {
        $relation = new Relation($this->client);
        $relation->setStartNode($this);
        $relation->setEndNode($to);
        $relation->setType($type);
        $relation->setDirection($direction);

        return $relation->getAll();
    }

    public function getLabels()
    {
        if ($this->labels === null) {
            $cypher = $this->compileGetLabels();
            $statement = new Statement($cypher, ['id' => $this->id]);
            $response = $this->client->runStatement($statement);
            $resultSet = new ResultSet($response);
            $this->labels = $resultSet->getResults()[0][0];
        }

        return $this->labels;
    }

    public function delete()
    {
        $cypher = $this->compileDeleteNode();
        $statement = new Statement($cypher, ['id' => $this->id]);
        $this->client->runStatement($statement);
    }
}
