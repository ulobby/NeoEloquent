<?php

namespace Vinelab\NeoEloquent\DatabaseDriver;

use Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis\Laudis;

class DatabaseDriver
{
    public static function create($config)
    {
        return new Laudis($config);
    }
}
