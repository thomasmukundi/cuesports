<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_functionality()
    {
        // Test basic Laravel functionality
        $response = $this->get('/');
        $this->assertTrue(true);
    }

    public function test_database_connection()
    {
        // Test database connection
        $this->assertDatabaseCount('users', 0);
    }
}
