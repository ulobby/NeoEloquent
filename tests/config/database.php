<?php

return [

    'default' => 'default',

    'connections' => [

        'neo4j' => [
            'scheme'   => 'bolt',
            'driver'   => 'neo4j',
            'host'     => 'localhost',
            'port'     => 7687,
            'username' => 'neo4j',
            'password' => 'test',
        ],

        'default' => [
            'scheme'   => 'bolt',
            'driver'   => 'neo4j',
            'host'     => 'localhost',
            'port'     => 7687,
            'username' => 'neo4j',
            'password' => 'test',
        ],
    ],
];
