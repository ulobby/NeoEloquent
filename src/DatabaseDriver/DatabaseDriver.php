<?php

namespace Vinelab\NeoEloquent\DatabaseDriver;

use Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis\Laudis;
use Vinelab\NeoEloquent\DatabaseDriver\Interfaces\ClientInterface;

class DatabaseDriver
{
    protected static function nameToClass($name)
    {
        $drivers = [
            'laudis' => Laudis::class,
        ];

        return $drivers[$name];
    }

    public static function create($config): ClientInterface
    {
        $eloquentDriver = $config['eloquent_driver'] ?? 'laudis';
        $className = self::nameToClass($eloquentDriver);

        return new $className($config);
    }
}
