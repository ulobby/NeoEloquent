<?php

namespace Vinelab\NeoEloquent\Tests\Eloquent;

use Vinelab\NeoEloquent\Eloquent\Collection;
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
    public function testContainsWithModels()
    {
        $person = Person::create(['name' => 'Johannes']);
        $office = Office::create(['name' => 'Denmark']);
        $office->members()->save($person);

        $this->assertTrue($office->members->contains($person));
    }

    public function testContainsWithVariables()
    {
        $collection = new Collection(['a', 'b']);

        $this->assertTrue($collection->contains('a'));
        $this->assertFalse($collection->contains('z'));

        $collection = new Collection([1, 2]);

        $this->assertTrue($collection->contains(1));
        $this->assertFalse($collection->contains(9));
    }
}
