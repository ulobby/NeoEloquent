<?php

namespace Vinelab\NeoEloquent\DatabaseDriver;

use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\ClientInterface;

/**
 * Represents a Cypher query string and variables
 * Query the database using Cypher. For query syntax, please refer
 * to the Cypher documentation for your server version.
 *
 * Latest documentation:
 * http://docs.neo4j.org/chunked/snapshot/cypher-query-lang.html
 */
class CypherQuery
{
    protected $client = null;

    protected $result = null;

    public function __construct(ClientInterface $client, protected $template, protected $vars = [])
    {
        $this->client = $client;
    }

    /**
     * Get the query script.
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->template;
    }

    /**
     * Get the template parameters.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->vars;
    }

    /**
     * Retrieve the query results.
     */
    public function getResultSet()
    {
        if ($this->result === null) {
            $this->result = $this->client->executeCypherQuery($this);
        }

        return $this->result;
    }
}
