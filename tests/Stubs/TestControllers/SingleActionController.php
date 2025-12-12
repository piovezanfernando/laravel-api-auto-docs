<?php

namespace Piovezanfernando\LaravelApiAutoDocs\Tests\Stubs\TestControllers;

use Illuminate\Routing\Controller;

class SingleActionController extends Controller
{
    public function __invoke()
    {
        return 'hello';
    }
}
