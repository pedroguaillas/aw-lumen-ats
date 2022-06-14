<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResources;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function listusser(Request $request)
    {
        $search = '';
        $paginate = 15;

        if ($request) {
            $search = $request->search;
            $paginate = $request->has('paginate') ? $request->paginate : $paginate;
        }

        $users = User::where('rol', 'asesor')
            ->where('name', 'LIKE', "%$search%")
            ->orderBy('created_at', 'DESC');

        return UserResources::collection($users->paginate($paginate));
    }

    public function customers(int $id, Request $request)
    {
        $user = User::find($id);

        $customers = DB::table('cliente_auditwholes')
            ->select('razonsocial', 'ruc', DB::raw("(SELECT SUM(amount) FROM payments WHERE cliente_auditwhole_ruc = ruc AND year = $request->year) AS total"))
            ->where('user_id', $id)
            ->get();

        return response()->json(['user' => $user, 'customers' => $customers]);
    }
}
