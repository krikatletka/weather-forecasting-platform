<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class DatabaseIsolationTest extends TestCase
{
    public function test_database_isolation(): void
    {
        $this->assertSame(
            'pgsql',
            config('database.default')
        );

        $this->assertSame(
            'weather_db_test',
            config('database.connections.pgsql.database')
        );

        $database = DB::selectOne(
            'SELECT current_database() AS name'
        );

        $this->assertSame(
            'weather_db_test',
            $database->name
        );
    }
}
