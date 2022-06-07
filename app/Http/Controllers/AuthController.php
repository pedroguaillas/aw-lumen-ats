<?php

namespace App\Http\Controllers;

use App\ClienteAuditwhole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;

class AuthController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function register(Request $request)
    {
        $name = $request->input('name');
        $user = $request->input('user');
        $email = $request->input('email');
        $rol = $request->input('rol');
        $password = Hash::make($request->input('password'));

        $register = User::create([
            'name' => $name,
            'user' => $user,
            'rol' => $rol,
            'email' => $email,
            'password' => $password
        ]);

        if ($register) {
            return response()->json([
                'success' => true,
                'message' => 'Register Success!',
                'data' => $register,
            ], 201);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Register Fail!',
                'data' => '',
            ], 401);
        }
    }

    public function login(Request $request)
    {
        $user = $request->input('user');
        $password = $request->input('password');

        $u = User::where('user', $user)->first();

        if (Hash::check($password, $u->password)) {
            $apiToken = base64_encode(str_random(40));

            $u->update([
                'remember_token' => $apiToken
            ]);

            if ($u->rol === 'admin') {
                // $customers = ClienteAuditwhole::all(['ruc', 'razonsocial', 'nombrecomercial', 'phone', 'mail', 'direccion', 'diadeclaracion', 'sri', 'representantelegal', 'iess1', 'iess2', 'mt', 'mrl', 'super', 'contabilidad']);
                // Solo clientes Auditwhole
                $customers = ClienteAuditwhole::select(['ruc', 'razonsocial', 'nombrecomercial', 'phone', 'mail', 'direccion', 'diadeclaracion', 'sri', 'representantelegal', 'iess1', 'iess2', 'mt', 'mrl', 'super', 'contabilidad'])
                    // Menos clientes Victor
                    ->where('user_id', '<>', 15)->get();
            } elseif ($u->rol === 'asesor') {
                $customers = $u->clienteauditwholes()->get(['ruc', 'razonsocial', 'nombrecomercial', 'phone', 'mail', 'direccion', 'diadeclaracion', 'sri', 'representantelegal', 'iess1', 'iess2', 'mt', 'mrl', 'super', 'contabilidad']);
            }

            return response()->json([
                'success' => true,
                'user' => $u,
                'remember_token' => $apiToken,
                'customers' => $customers
            ], 201);
        } else {
            return response()->json([
                'success' => false,
                'data' => ''
            ], 401);
        }
    }
}
