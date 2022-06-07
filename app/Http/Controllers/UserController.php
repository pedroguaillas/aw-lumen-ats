<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResources;
use App\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function listusser(Request $request)
    {
        $paginate = 15;

        if ($request) {
            $paginate = $request->has('paginate') ? $request->paginate : $paginate;
        }

        $users = User::where('rol', 'asesor')
            ->orderBy('created_at', 'DESC');

        return UserResources::collection($users->paginate($paginate));
    }
}
