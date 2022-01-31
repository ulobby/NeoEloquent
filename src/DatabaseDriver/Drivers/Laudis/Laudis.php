<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis;

use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\Client;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\AuthenticateInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Formatter\OGMFormatter;
use Laudis\Neo4j\Formatter\SummarizedResultFormatter;
use Vinelab\NeoEloquent\DatabaseDriver\CypherQuery;
use Vinelab\NeoEloquent\DatabaseDriver\Drivers\ClientAbstract;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\ClientInterface;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\NodeInterface;

class Laudis extends ClientAbstract implements ClientInterface
{
    /**
     * @var Client
     */
    protected $client;
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
        $formatter = new SummarizedResultFormatter(OGMFormatter::create());
        $client = ClientBuilder::create()
            ->withDriver('default', $this->buildUriFromConfig($config), $this->getAuth())
            ->withFormatter($formatter)
            ->build();
        $this->client = $client;
    }

    public function makeNode(): Node
    {
        return new Node($this->client);
    }

    public function makeRelationship(): Relation
    {
        return new Relation($this->client);
    }

    public function makeLabel($label)
    {
        return $label;
    }

    public function executeCypherQuery(CypherQuery $cypherQuery): ResultSet
    {
        $statement = new Statement($cypherQuery->getQuery(), $cypherQuery->getParameters());

        return new ResultSet(
            $this->client->runStatement($statement)
        );
    }

    public function run($cypher)
    {
        return $this->client->run($cypher);
    }

    private function getAuth(): AuthenticateInterface
    {
        $username = $this->getUsername();
        $password = $this->getPassword();
        if ($username && $password) {
            return Authenticate::basic($username, $password);
        }

        return Authenticate::disabled();
    }

    public function getNode($id): Node
    {
        $node = $this->makeNode();
        $node->setId($id);
        $node->populateNode();

        return $node;
    }

    public function deleteNode(NodeInterface $node)
    {
        $node->delete();
    }

    public function startBatch()
    {
        // TODO
        return new Batch();
    }

    public function commitBatch()
    {
        // TODO
        return true;
    }

    public function beginTransaction()
    {
        // TODO
    }
}
