<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    protected function beforeRefreshingDatabase(): void {
        if (config('database.default')!=='sqlite' || config('database.connections.sqlite.database')!==':memory:') throw new \RuntimeException('An in-memory test database is required.');
    }
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
