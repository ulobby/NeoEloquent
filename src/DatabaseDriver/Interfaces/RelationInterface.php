<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Interfaces;

use Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis\Node;
use Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis\Relation;

interface RelationInterface
{
    const DIRECTION_ALL = 'all';
    const DIRECTION_IN = 'in';
    const DIRECTION_OUT = 'out';

    public function save();

    public function getProperties();

    public function setType($type): Relation;

    public function getStartNode(): Node;

    public function getEndNode(): Node;

    public function getId();

    public function setEndNode($end): Relation;

    public function setStartNode($start): Relation;

    public function setProperties($properties): Relation;

    public function setProperty($key, $value): Relation;

    public function hasId(): bool;

    public function delete();

    public function setDirection($direction): Relation;
}
