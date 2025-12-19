<?php

/**
 * Custom API Response Examples
 *
 * This file allows you to define static response examples for your API endpoints
 * that will be displayed in the documentation without executing the actual routes.
 *
 * USAGE:
 * 1. Copy this file to your project root as 'custom-responses.php'
 * 2. Define responses using 'ControllerName@method' as the key
 * 3. Only define the response data (the actual content to return)
 *
 * FORMAT:
 * 'ControllerName@method' => [
 *     // Your response data here
 * ],
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

    'AuthController@login' => [
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

    'AuthController@register' => [
        'user' => [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => null,
            'created_at' => '2024-01-15T10:00:00.000000Z',
            'updated_at' => '2024-01-15T10:00:00.000000Z',
        ],
        'token' => '2|laravel_sanctum_example_token_abc123xyz789',
        'token_type' => 'Bearer',
        'expires_in' => 3600,
    ],

    'AuthController@logout' => [
        'message' => 'Successfully logged out',
    ],

    'AuthController@me' => [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'email_verified_at' => '2024-01-15T10:00:00.000000Z',
        'created_at' => '2024-01-10T08:30:00.000000Z',
        'updated_at' => '2024-01-15T14:20:00.000000Z',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Examples (CRUD)
    |--------------------------------------------------------------------------
    */

    'UserController@index' => [
        'data' => [
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'created_at' => '2024-01-10T08:30:00.000000Z',
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'created_at' => '2024-01-11T09:15:00.000000Z',
            ],
        ],
        'meta' => [
            'total' => 50,
            'per_page' => 15,
            'current_page' => 1,
            'last_page' => 4,
        ],
    ],

    'UserController@show' => [
        'data' => [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => '2024-01-15T10:00:00.000000Z',
            'created_at' => '2024-01-10T08:30:00.000000Z',
            'updated_at' => '2024-01-15T14:20:00.000000Z',
        ],
    ],

    'UserController@store' => [
        'data' => [
            'id' => 3,
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'created_at' => '2024-01-16T10:00:00.000000Z',
            'updated_at' => '2024-01-16T10:00:00.000000Z',
        ],
        'message' => 'User created successfully',
    ],

    'UserController@update' => [
        'data' => [
            'id' => 1,
            'name' => 'John Doe Updated',
            'email' => 'john.updated@example.com',
            'created_at' => '2024-01-10T08:30:00.000000Z',
            'updated_at' => '2024-01-16T11:30:00.000000Z',
        ],
        'message' => 'User updated successfully',
    ],

    'UserController@destroy' => [
        'message' => 'User deleted successfully',
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
