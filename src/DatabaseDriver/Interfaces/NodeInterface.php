<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Interfaces;

interface NodeInterface
{
    public function setProperty($key, $value);
    public function save();
    public function getId();
    public function setId($id): NodeInterface;
    public function addLabels($labels);
    public function getRelationships($type, $direction): array;
    public function getProperties(): array;
    public function findPathsTo(NodeInterface $to, $type = null, $direction = null): array;
    public function delete();
}
