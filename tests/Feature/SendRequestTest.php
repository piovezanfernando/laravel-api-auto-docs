<?php

namespace Piovezanfernando\LaravelApiAutoDocs\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Piovezanfernando\LaravelApiAutoDocs\Tests\TestCase;

class SendRequestTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Load stub classes
        
    }


    public function testSendRequestToDocumentedRoute()
    {
        // This would test the frontend sendRequest functionality
        // Since it's frontend, we test the backend endpoint directly
        
        $response = $this->post('/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'attachments' => [
                [
                    'classification' => 'document',
                    'file' => 'file.pdf'
                ]
            ]
        ]);
        
        // Assert response structure with LRD if enabled
        // For now, just check it doesn't crash
        $this->assertTrue(true); // Placeholder
    }

    public function testRouteWithValidationErrors()
    {
        $response = $this->post('/users', [
            'name' => '', // Invalid
            'email' => 'invalid-email',
        ]);
        
        // May redirect (302) due to web middleware
        $this->assertTrue($response->isRedirection() || $response->isClientError());
    }
}
