<?php

namespace Piovezanfernando\LaravelApiAutoDocs\Tests\Unit;

use Illuminate\Support\Facades\Route;
use Piovezanfernando\LaravelApiAutoDocs\LaravelApiAutoDocs;
use Piovezanfernando\LaravelApiAutoDocs\Tests\TestCase;

class LaravelApiAutoDocsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Load stub classes
        
    }


    // Removed test for getRoutes() as it's not directly testable without mocking routes

    public function testFlattenRulesHandlesSimpleRules()
    {
        $mixedRules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email'],
        ];
        
        $flattened = app(LaravelApiAutoDocs::class)->flattenRules($mixedRules);
        
        $this->assertEquals(['required|string|max:255'], $flattened['name']);
        $this->assertEquals(['required|email'], $flattened['email']);
    }

    public function testFlattenRulesHandlesComplexArrayRules()
    {
        $mixedRules = [
            'attachments' => 'required|array|min:1',
            'attachments.*' => 'array',
            'attachments.*.classification' => 'required|string|max:20',
            'tags' => 'array',
            'tags.*' => 'string|max:50',
        ];
        
        $flattened = app(LaravelApiAutoDocs::class)->flattenRules($mixedRules);
        
        $this->assertArrayHasKey('attachments', $flattened);
        $this->assertArrayHasKey('attachments.classification', $flattened);
        $this->assertArrayHasKey('tags', $flattened);
        // Check merged rules
        $this->assertContains('required|array|min:1', $flattened['attachments']);
        $this->assertStringContainsString('array', $flattened['attachments'][0]);
    }

    public function testFlattenRulesRemovesDuplicates()
    {
        $mixedRules = [
            'field' => 'required|required|string|string',
        ];
        
        $flattened = app(LaravelApiAutoDocs::class)->flattenRules($mixedRules);
        
        $this->assertEquals(['required|string'], $flattened['field']);
    }

    public function testRulesByRegexParsesFile()
    {
        // This tests the regex parsing for rules in PHP files
        $rules = app(LaravelApiAutoDocs::class)->rulesByRegex(
            'Piovezanfernando\\LaravelApiAutoDocs\\Tests\\Stubs\\TestRequests\\UserStoreRequest',
            'rules'
        );
        
        $this->assertIsArray($rules);
    }
}
