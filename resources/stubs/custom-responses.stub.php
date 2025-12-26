<?php

/**
 * Custom API Response Examples
 *
 * This file allows you to define static response examples for your API endpoints
 * that will be displayed in the documentation without executing the actual routes.
 *
 * NOTES:
 * - Use short controller name (without namespace)
 * - Example: 'UserController@index' not 'App\Http\Controllers\UserController@index'
 * - The 200 OK response will use this data
 * - Error responses (400, 401, 404, etc) are defined globally in config/api-docs.php
 * - Templates by HTTP method are also in config/api-docs.php
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Examples
    |--------------------------------------------------------------------------
    */

    'AuthController@authenticate' => [
        'token' => '2|laravel_sanctum_example_token_abc123xyz789',
        'token_type' => 'Bearer',
        'expires_in' => 3600,
        'user' => [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => '2024-01-15T10:00:00.000000Z',
            'created_at' => '2024-01-10T08:30:00.000000Z',
            'updated_at' => '2024-01-15T14:20:00.000000Z',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Add Your Custom Responses Below
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | 'YourController@yourMethod' => [
    |     'custom_field' => 'value',
    |     'another_field' => 123,
    |     'nested' => [
    |         'data' => 'here',
    |     ],
    | ],
    |
    */
];
