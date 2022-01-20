<?php

namespace Vinelab\NeoEloquent\Tests\Eloquent;

use Vinelab\NeoEloquent\Eloquent\Model as NeoEloquent;
use Vinelab\NeoEloquent\Tests\TestCase;

class Office extends NeoEloquent
{
    protected $label = 'Office';
    protected $guarded = [];

    public function members()
    {
        return $this->belongsToMany(Person::class, 'MEMBER_OF');
    }
}

class Person extends NeoEloquent
{
    protected $guarded = [];
    protected $label = 'Person';
}

class CollectionTest extends TestCase
{
//    public function tearDown(): void
//    {
//        $all = Person::all();
//        $all->each(function ($u) { $u->delete(); });
//
//        parent::tearDown();
//    }

    public function testContainsWithModels()
    {
        $person = Person::create(['name' => 'Johannes']);
        $office = Office::create(['name' => 'Denmark']);
        $office->members()->save($person);

        $this->assertTrue($office->members->contains($person));
    }

    public function testContainsWithVariables()
    {
        $this->markTestSkipped('TODO');
        $person = Person::create(['name' => 'Johannes']);
        $office = Office::create(['name' => 'Denmark']);
        $office->members()->save($person);

        $this->assertTrue($office->members->contains($person));
    }
}