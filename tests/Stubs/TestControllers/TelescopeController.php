<?php

namespace Piovezanfernando\LaravelApiAutoDocs\Tests\Stubs\TestControllers;

use Illuminate\Routing\Controller;

class TelescopeController extends Controller
{
    public function index()
    {
        return 'telescope';
    }
}
