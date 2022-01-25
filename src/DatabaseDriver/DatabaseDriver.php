<?php

namespace Vinelab\NeoEloquent\DatabaseDriver;

use Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis\Laudis;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\ClientInterface;

class DatabaseDriver
{
    public static function create($config): ClientInterface
    {
        return new Laudis($config);
    }
}
