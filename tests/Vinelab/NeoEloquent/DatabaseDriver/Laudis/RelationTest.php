<?php

namespace Vinelab\NeoEloquent\Tests\DatabaseDriver\Laudis;

use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis\Node;
use Vinelab\NeoEloquent\DatabaseDriver\Drivers\Laudis\Relation;

class RelationTest extends TestCase
{
    /** @var list<Statement> */
    private array $statements = [];

    private ClientInterface $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = M::mock(ClientInterface::class);
        $this->client->shouldReceive('runStatement')->andReturnUsing(function (Statement $statement) {
            $this->statements[] = $statement;

            $summary = null;

            return new SummarizedResult($summary, [new CypherMap(['id(r)' => 100 + count($this->statements)])]);
        });
    }

    protected function tearDown(): void
    {
        M::close();

        parent::tearDown();
    }

    public function testStatementsKeepTheSameCypherForDifferentIdsAndPassTheIdsAsParameters()
    {
        $first = $this->relation(1, 2);
        $second = $this->relation(30, 40);

        $first->save();
        $second->save();
        [$createFirst, $createSecond] = $this->takeStatements();
        $this->assertSame($createFirst->getText(), $createSecond->getText());
        $this->assertSame(['since' => 2020, '__start_id' => 1, '__end_id' => 2], $createFirst->getParameters());
        $this->assertSame(['since' => 2020, '__start_id' => 30, '__end_id' => 40], $createSecond->getParameters());

        $first->setProperty('since', 2021)->save();
        $second->setProperty('since', 2021)->save();
        [$updateFirst, $updateSecond] = $this->takeStatements();
        $this->assertSame($updateFirst->getText(), $updateSecond->getText());
        $this->assertSame(['since' => 2021, '__start_id' => 30, '__end_id' => 40, '__rel_id' => 102], $updateSecond->getParameters());

        $first->getAll();
        $second->getAll();
        $first->delete();
        $second->delete();
        [$getFirst, $getSecond, $deleteFirst, $deleteSecond] = $this->takeStatements();
        $this->assertSame($getFirst->getText(), $getSecond->getText());
        $this->assertSame($deleteFirst->getText(), $deleteSecond->getText());
        $this->assertSame(102, $deleteSecond->getParameters()['__rel_id']);
    }

    private function relation(int $startId, int $endId): Relation
    {
        return (new Relation($this->client))
            ->setType('KNOWS')
            ->setDirection('out')
            ->setStartNode((new Node($this->client))->setId($startId))
            ->setEndNode((new Node($this->client))->setId($endId))
            ->setProperties(['since' => 2020]);
    }

    /**
     * @return list<Statement>
     */
    private function takeStatements(): array
    {
        $statements = $this->statements;
        $this->statements = [];

        return $statements;
    }
}
