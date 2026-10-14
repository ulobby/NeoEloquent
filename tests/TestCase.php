<?php

namespace Vinelab\NeoEloquent\Tests;

use Mockery as M;
use PHPUnit\Framework\TestCase as PHPUnit;
use Vinelab\NeoEloquent\Connection;
use Vinelab\NeoEloquent\Eloquent\Model;

class Stub extends Model
{
}

class TestCase extends PHPUnit
{
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // load custom configuration file
        $this->dbConfig = require 'config/database.php';
    }

    public function setUp(): void
    {
        parent::setUp();

        $resolver = M::mock('Illuminate\Database\ConnectionResolverInterface');
        $resolver->shouldReceive('connection')->andReturn($this->getConnectionWithConfig('default'));

        Stub::setConnectionResolver($resolver);
    }

    public function tearDown(): void
    {
        // everything should be clean before every test
        $this->flushDb();

        parent::tearDown();
    }

    public static function setUpBeforeClass(): void
    {
        date_default_timezone_set('Asia/Beirut');
    }

    /**
     * Get the connection with a given or the default configuration.
     *
     * @param string $config As specified in config/database.php
     *
     * @return \Vinelab\NeoEloquent\Connection
     */
    protected function getConnectionWithConfig($config = null)
    {
        $connection = is_null($config) ? $this->dbConfig['connections']['default'] :
                                         $this->dbConfig['connections'][$config];

        return new Connection($connection);
    }

    /**
     * Assert two sets of models have the same attributes, ignoring order:
     * Neo4j does not guarantee result order without an ORDER BY.
     *
     * @param iterable $expected
     * @param iterable $actual
     */
    protected function assertSameModels($expected, $actual)
    {
        $normalize = function ($models) {
            $byKey = [];
            foreach ($models as $model) {
                $byKey[$model->getKey()] = $model->toArray();
            }
            ksort($byKey);

            return $byKey;
        };

        $this->assertEquals($normalize($expected), $normalize($actual));
    }

    /**
     * Flush all database records.
     *
     * @return void
     */
    protected function flushDb()
    {
        $client = $this->getClient();

        $flushQuery = 'MATCH (n) DETACH DELETE n';

        $client->run($flushQuery);
    }

    protected function getClient()
    {
        $connection = (new Stub())->getConnection();

        return $connection->getClient();
    }
}
