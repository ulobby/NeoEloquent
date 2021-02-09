<?php

namespace Vinelab\NeoEloquent\Eloquent\Relations;

interface RelationInterface
{
    /**
     * Get the direction of the edge for this relationship.
     *
     * @return string
     */
    public function getEdgeDirection();

    /**
     * Get the relation name.
     *
     * @return string
     */
    public function getRelationName();

    /**
     * Get the relationship type (label in other words),
     * [:FOLLOWS] etc.
     *
     * @return string
     */
    public function getRelationType();

    /**
     * Get the parent model's Node placeholder.
     *
     * @return string
     */
    public function getParentNode();

    /**
     * Get the related model's Node placeholder.
     *
     * @return string
     */
    public function getRelatedNode();

    /**
     * Returns the child of the relationship.
     *
     * @return Model
     */
    public function getRelated();

    /**
     * Returns the parent of the relationship.
     *
     * @return Model
     */
    public function getParent();

    /**
     * Get the localKey.
     *
     * @return string
     */
    public function getLocalKey();

    /**
     * Get the parent model's value according to $localKey.
     *
     * @return mixed
     */
    public function getParentLocalKeyValue();
}
