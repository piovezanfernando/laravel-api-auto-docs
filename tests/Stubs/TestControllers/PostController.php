<?php

namespace Piovezanfernando\LaravelApiAutoDocs\Tests\Stubs\TestControllers;

use Illuminate\Http\Request;

class PostController
{
    public function show(Request $request, $id)
    {
        return response()->json([
            'post' => [
                'id' => $id,
                'title' => 'Sample Post',
                'content' => 'This is a sample post content.',
                'author' => ['id' => 1, 'name' => 'John Doe']
            ]
        ]);
    }
}
