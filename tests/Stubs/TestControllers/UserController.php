<?php

namespace Piovezanfernando\LaravelApiAutoDocs\Tests\Stubs\TestControllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Piovezanfernando\LaravelApiAutoDocs\Tests\Stubs\TestRequests\UserStoreRequest;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }

    public function show($id)
    {
        return response()->json(['id' => $id]);
    }

    public function store(UserStoreRequest $request)
    {
        return response()->json($request->validated(), 201);
    }
}
