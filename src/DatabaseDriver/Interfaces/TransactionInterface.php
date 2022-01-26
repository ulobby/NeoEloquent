<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Interfaces;

interface TransactionInterface
{
    public function commit();

    public function rollBack();
}