<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Interfaces;

use Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis\Node;

interface NodeInterface
{
    public function setProperty($key, $value);
    public function save();
    public function getId();
    public function setId($id): Node;
    public function addLabels($labels);
    public function getRelationships(): array;
    public function getProperties(): array;
    public function findPathsTo();
}
