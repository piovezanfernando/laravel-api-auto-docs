<?php

namespace Piovezanfernando\LaravelApiAutoDocs\Tests\Stubs\TestControllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AccountController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }

    public function show($id)
    {
        return response()->json(['id' => $id]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'account_number' => 'required|string',
        ]);
        return response()->json($request->all(), 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'account_number' => 'string',
        ]);
        return response()->json($request->all());
    }

    public function destroy($id)
    {
        return response()->json(null, 204);
    }
}
