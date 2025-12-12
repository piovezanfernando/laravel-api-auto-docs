<?php

namespace Piovezanfernando\LaravelApiAutoDocs\Tests\Stubs\TestControllers;

use Illuminate\Routing\Controller;

class MatchController extends Controller
{
    public function index()
    {
        return 'matched';
    }
}
