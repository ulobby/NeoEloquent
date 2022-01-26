<?php

namespace Vinelab\NeoEloquent;

use Exception;

class QueryException extends Exception
{
    public function __construct($query)
    {
        parent::__construct($query);
        // TODO
    }
}
