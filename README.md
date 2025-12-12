# Laravel API Auto Docs

<p align="center">
  <img src="https://img.shields.io/badge/license-MIT-green.svg" alt="License MIT">
  <img src="https://img.shields.io/badge/php-8.4%2B-green" alt="PHP 8.4+">
</p>

<p align="center">
  The Hassle-Free automatic API documentation generation for Laravel.
  <br>
  A Swagger alternative with modern Vue 3 interface.
  <br>
  Supports Open API 3.0.0
</p>

## Features

- ✨ Light and Dark mode
- 🚀 Automatic rules fetching from injected Request and by regexp
- 📋 Automatic routes fetching from Laravel Routes
- 📝 Support for Laravel logs
- 🗃️ Support for SQL query and query time analysis
- ⚡ Support for memory consumption
- 🔐 Support for Authorization Headers
- 🎯 Support for Eloquent events
- 🔄 Support for OpenAPI 3.0.0 exports
- 🎨 Modern Vue 3 interface

## Requirements

- PHP >= 8.1
- Laravel >= 11.0

## Installation

You can install the package via composer:

```bash
composer require piovezanfernando/laravel-api-auto-docs
```

Publish the config file:

```bash
php artisan vendor:publish --tag="api-auto-docs-config"
```

## Configuration

The configuration file will be published to `config/api-auto-docs.php`. Here you can configure:

- Routes to document
- Middleware to apply
- UI customizations
- And more...

## Usage

### Accessing the Documentation

By default, the documentation is available at:

```
http://your-app.test/docs-api
```

### Generating OpenAPI Specification

To generate the OpenAPI specification file:

```bash
php artisan api-docs:export
```

This will create an `api.json` file in your project's root directory by default. You can specify a different path:

```bash
php artisan api-docs:export storage/app/api.json
```

### Adding Descriptions to Controllers

The package automatically reads the summary and description from your controller methods' PHPDoc comments.

```php
class UserController extends Controller
{
    /**
     * Create a new user.
     *
     * This endpoint allows you to create a new user in the system.
     * The user's name, email, and password are required.
     */
    public function store(CreateUserRequest $request)
    {
        // Your code here
    }
}
```

### Request Validation

The package automatically reads Laravel FormRequest validation rules:

```php
class CreateUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ];
    }
}
```

## Configuration Options

### Exclude Routes

In `config/api-auto-docs.php`:

```php
'hide_matching' => [
    '#^api/internal#',
    '#^_debugbar#',
],
```

### Middleware

```php
'middlewares' => [
    'web',
    'auth', // Add if you want authentication
],
```

### UI Customization

```php
'title' => 'My API Documentation',
'default_headers' => [
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
],
```

## Testing Your API

The built-in UI allows you to:
- 📤 Send requests directly from the browser
- 📊 View SQL queries and memory usage
- 📋 See logs and debug information
- 💾 Export to OpenAPI format

## Export Options

### OpenAPI 3.0

Access the OpenAPI specification:
```
http://your-app.test/docs-api/api?openapi=true
```

Download the JSON representation via:
```
http://your-app.test/docs-api/api?json=true
```

## Security

If you want to restrict access to the documentation:

```php
// config/api-auto-docs.php
'middlewares' => [
    'web',
    'auth',
    'can:view-api-docs', // Custom gate
],
```

### Credits

This package is based on the excellent work from  
[Laravel Request Docs](https://github.com/rakutentech/laravel-request-docs) by Rakuten Tech.

All credit for the original idea and implementation goes to  
Pulkit Kathuria and the Rakuten Tech engineering team.

This fork extends the original project with additional features, UI redesign,
and ongoing maintenance, while preserving the MIT license.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

- **Issues**: [GitHub Issues](https://github.com/piovezanfernando/laravel-api-auto-docs/issues)
- **Original Project**: [Laravel Request Docs](https://github.com/rakutentech/laravel-request-docs)

## Colaboradores

<a href="https://github.com/piovezanfernando/laravel-api-auto-docs/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=piovezanfernando/laravel-api-auto-docs"  alt=""/>
</a>