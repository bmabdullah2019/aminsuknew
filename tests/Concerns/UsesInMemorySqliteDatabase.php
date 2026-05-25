<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

trait UsesInMemorySqliteDatabase
{
    protected function useInMemorySqliteDatabase(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('database.connections.sqlite.foreign_key_constraints', false);
        // Avoid file cache writes during tests (storage permissions / CI).
        config()->set('cache.default', 'array');

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        DB::setDefaultConnection('sqlite');
    }
}
