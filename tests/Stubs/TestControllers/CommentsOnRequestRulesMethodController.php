<?php

namespace Piovezanfernando\LaravelApiAutoDocs\Tests\Stubs\TestControllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CommentsOnRequestRulesMethodController extends Controller
{
    public function index(Request $request)
    {
        $request->validate($this->rules());
        return 'ok';
    }

    /**
     * @rules
     * 'name' => 'required|string',
     * 'email' => 'required|email',
     */
    private function rules(): array
    {
        return [];
    }
}
