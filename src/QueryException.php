<?php

namespace Vinelab\NeoEloquent;

use Exception;

class QueryException extends Exception
{
    public function __construct($query, $bindings = [], $exception = null)
    {
        $message = '';

        if ($exception !== null) {
            $message = $exception->getMessage();
        }

        $message .= ' - Query: '.$query;

        if (!empty($bindings)) {
            $message .= ' - Bindings: '.json_encode($bindings);
        }

        parent::__construct($message);
    }
}
