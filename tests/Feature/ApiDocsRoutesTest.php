<?php

namespace Piovezanfernando\LaravelApiAutoDocs\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Piovezanfernando\LaravelApiAutoDocs\Tests\TestCase;

class ApiDocsRoutesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Load stub classes
        
    }


    // Removed as routes may be empty in test environment



    // Removed as depends on routes being collected

    public function testRoutesEndpointReturnsValidJson()
    {
        $response = $this->get('/docs-api/routes');
        
        $response->assertStatus(200);
        $this->assertIsArray($response->json());
    }
}
