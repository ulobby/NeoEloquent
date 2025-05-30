<?php

namespace Vinelab\NeoEloquent\Eloquent\Edges;

use Vinelab\NeoEloquent\Connection;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\BatchInterface;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\ClientInterface;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\NodeInterface;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\RelationInterface;
use Vinelab\NeoEloquent\Eloquent\Builder;
use Vinelab\NeoEloquent\Eloquent\Model;
use Vinelab\NeoEloquent\QueryException;
use Vinelab\NeoEloquent\UnknownDirectionException;

abstract class Delegate
{
    /**
     * The Eloquent builder instance.
     *
     * @var \Vinelab\NeoEloquent\Eloquent\Builder
     */
    protected $query;

    /**
     * The database connection.
     *
     * @var \Vinelab\NeoEloquent\Connection
     */
    protected $connection;

    /**
     * The database client.
     *
     * @var ClientInterface
     */
    protected $client;

    /**
     * Create a new delegate instance.
     *
     * @param \Vinelab\NeoEloquent\Eloquent\Builder $query
     * @param Model                                 $parent
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
        $model = $query->getModel();

        // Setup the database connection and client.
        $this->connection = $model->getConnection();
        $this->client = $this->connection->getClient();
    }

    /**
     * Get a new Finder instance.
     *
     * @return \Vinelab\NeoEloquent\Eloquent\Edges\Finder
     */
    public function newFinder()
    {
        return new Finder($this->query);
    }

    /**
     * Make a new Relationship instance.
     *
     * @param string $type
     * @param Model  $startModel
     * @param Model  $endModel
     * @param array  $properties
     *
     * @return RelationInterface
     */
    protected function makeRelationship($type, $startModel, $endModel, $properties = []): RelationInterface
    {
        return $this->client
            ->makeRelationship()
            ->setType($this->type)
            ->setStartNode($this->start)
            ->setEndNode($this->end)
            ->setProperties($this->attributes);
    }

    /**
     * Start a batch operation with the database.
     *
     * @return BatchInterface
     */
    public function prepareBatch()
    {
        return $this->client->startBatch();
    }

    /**
     * Commit the started batch operation.
     *
     * @throws \Vinelab\NeoEloquent\QueryException If no open batch to commit.
     *
     * @return bool
     */
    public function commitBatch()
    {
        try {
            return $this->client->commitBatch();
        } catch (\Exception $e) {
            throw new QueryException('Error committing batch operation.', [], $e);
        }
    }

    /**
     * Get the direction value from the Neo4j
     * client according to the direction set on
     * the inheriting class,.
     *
     * @param string $direction
     *
     * @throws UnknownDirectionException If the specified $direction is not one of in, out or inout
     *
     * @return string
     */
    public function getRealDirection($direction)
    {
        if ($direction == 'in' || $direction == 'out') {
            $direction = ucfirst($direction);
        } elseif ($direction == 'any') {
            $direction = 'All';
        } else {
            throw new UnknownDirectionException($direction);
        }

        $direction = 'DIRECTION_'.mb_strtoupper($direction);

        return constant("\Vinelab\NeoEloquent\DatabaseDriver\Interfaces\RelationInterface::".$direction);
    }

    /**
     * Convert a model to a Node object.
     *
     * @param Model $model
     *
     * @return NodeInterface
     */
    public function asNode(Model $model): NodeInterface
    {
        $node = $this->client->makeNode();

        // If the key name of the model is 'id' we will need to set it properly with setId()
        // since setting it as a regular property with setProperty() won't cut it.
        if ($model->getKeyName() == 'id') {
            $node->setId($model->getKey());
        }

        // In this case the dev has chosen a different primary key
        // so we use it insetead.
        else {
            $node->setProperty($model->getKeyName(), $model->getKey());
        }

        return $node;
    }

    /**
     * Get the NeoEloquent connection for this relation.
     *
     * @return \Vinelab\NeoEloquent\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the database connection.
     *
     * @param \Vinelab\NeoEloquent\Connection $name
     *
     * @return void
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the current connection name.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->query->getModel()->getConnectionName();
    }
}
