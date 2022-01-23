<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Interfaces;

use Vinelab\NeoEloquent\DatabaseDriver\CypherQuery;

interface ClientInterface
{
    public function run($cypher);
    public function makeNode();
    public function makeLabel($label);
    public function executeCypherQuery(CypherQuery $cypherQuery);
    public function makeRelationship();
}