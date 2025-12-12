<?php

use Illuminate\Support\Facades\Route;
use Piovezanfernando\LaravelApiAutoDocs\Controllers\LaravelApiAutoDocsController;

if (!config('api-auto-docs.enabled', true)) {
    return;
}
Route::get(config('api-auto-docs.url', 'docs-api'), [LaravelApiAutoDocsController::class, 'index'])
    ->name('docs-api.index')
    ->middleware(config('api-auto-docs.middlewares', []));

Route::get('docs-api/routes', [LaravelApiAutoDocsController::class, 'getRoutesList'])
    ->name('docs-api.routes.list')
    ->middleware(config('api-auto-docs.middlewares', []));

Route::get('docs-api/routes/{id}', [LaravelApiAutoDocsController::class, 'getRouteDetails'])
    ->name('docs-api.routes.details')
    ->middleware(config('api-auto-docs.middlewares', []));

Route::get('docs-api/config', [LaravelApiAutoDocsController::class, 'config'])
    ->name('docs-api.config')
    ->middleware(config('api-auto-docs.middlewares', []));

Route::get('docs-api/api', [LaravelApiAutoDocsController::class, 'api'])
    ->name('docs-api.api')
    ->middleware(config('api-auto-docs.middlewares', []));