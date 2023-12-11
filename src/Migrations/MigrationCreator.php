<?php

namespace Vinelab\NeoEloquent\Migrations;

use Illuminate\Database\Migrations\MigrationCreator as IlluminateMigrationCreator;

class MigrationCreator extends IlluminateMigrationCreator
{
    /**
     * Create a new migration at the given path.
     *
     * @param  string  $name
     * @param  string  $path
     * @param  string|null  $table
     * @param  bool  $create
     * @return string
     *
     * @throws \Exception
     */
    public function create($name, $path, $table = null, $create = false)
    {
        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        // First we will get the stub file for the migration, which serves as a type
        // of template for the migration. Once we have those we will populate the
        // various place-holders, save the file, and run the post create event.
        $stub = $this->getStub($table, $create);

        $path = $this->getPath($name, $path);

        $this->files->ensureDirectoryExists(dirname($path));

        if ($table === null) {
            $table = $name;
        }

        if ($table !== null) {
            $stub = $this->populateStub($stub, $table);
        } else {
            $stub = $this->populateStub($stub, $name);
        }


        $this->files->put(
            $path, $stub
        );

        // Next, we will fire any hooks that are supposed to fire after a migration is
        // created. Once that is done we'll be ready to return the full path to the
        // migration file so it can be used however it's needed by the developer.
        $this->firePostCreateHooks($table, $path);

        return $path;
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param string $name
     * @param string $stub
     * @param string $label
     *
     * @return string
     */
    protected function populateStub($stub, $table)
    {
        $stub = str_replace(
            ['DummyClass', '{{ class }}', '{{class}}'],
            $this->getClassName($table),
            $stub
        );

        // Here we will replace the label place-holders with the label specified by
        // the developer, which is useful for quickly creating a labels creation
        // or update migration from the console instead of typing it manually.
        if (!is_null($table)) {
            $stub = str_replace(
                ['DummyTable', '{{ table }}', '{{table}}'],
                $table,
                $stub
            );
        }

        return $stub;
    }

    /**
     * {@inheritdoc}
     */
    public function stubPath()
    {
        return __DIR__.'/stubs';
    }
}
