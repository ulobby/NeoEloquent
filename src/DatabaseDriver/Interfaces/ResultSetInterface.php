<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Interfaces;

interface ResultSetInterface
{
    public function valid();

    public function getResults();
}
