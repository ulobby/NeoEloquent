<?php

return [

    'default' => 'default',

    'connections' => [

        'neo4j' => [
            'scheme'   => 'http',
            'driver'   => 'neo4j',
            'host'     => 'localhost',
            'port'     => 7474,
            'username' => 'neo4j',
            'password' => 'password',
        ],

        'default' => [
            'scheme'   => 'http',
            'driver'   => 'neo4j',
            'host'     => 'localhost',
            'port'     => 7474,
            'username' => 'neo4j',
            'password' => 'password',
        ],
    ],
];
