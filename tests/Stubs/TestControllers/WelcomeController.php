<?php

namespace Piovezanfernando\LaravelApiAutoDocs\Tests\Stubs\TestControllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WelcomeController extends Controller
{
    public function index()
    {
        return 'welcome';
    }

    public function show($id)
    {
        return "welcome {$id}";
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
        ]);
        return response()->json($request->all(), 201);
    }

    public function edit()
    {
        return 'edited';
    }

    public function destroy()
    {
        return 'destroyed';
    }

    public function health()
    {
        return 'healthy';
    }

    public function noRules()
    {
        return 'no-rules';
    }
}
