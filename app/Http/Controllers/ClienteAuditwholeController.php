<?php

namespace App\Http\Controllers;

use App\ClienteAuditwhole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ClienteAuditwholeResources;

class ClienteAuditwholeController extends Controller
{
    public function customerlist(Request $request)
    {
        $search = '';
        $paginate = 15;

        if ($request) {
            $search = $request->search;
            $paginate = $request->has('paginate') ? $request->paginate : $paginate;
        }

        $customers = ClienteAuditwhole::join('users', 'id', 'user_id')
            ->select('ruc', 'razonsocial', 'name', 'amount')
            ->where(function ($query) use ($search) {
                return $query->where('ruc', 'LIKE', "%$search%")
                    ->orWhere('razonsocial', 'LIKE', "%$search%");
            })
            ->orderBy('cliente_auditwholes.created_at', 'DESC');

        return ClienteAuditwholeResources::collection($customers->paginate($paginate));
    }

    public function store(Request $request)
    {
        $register = ClienteAuditwhole::create([
            'ruc' => $request->get('ruc'),
            'user_id' => $request->get('user_id'),
            'razonsocial' => $request->get('razonsocial'),
            'nombrecomercial' => $request->get('nombrecomercial'),
            'phone' => $request->get('phone'),
            'mail' => $request->get('mail'),
            'direccion' => $request->get('direccion'),
            'diadeclaracion' => $request->get('diadeclaracion'),
            'sri' => $request->get('sri'),
            'representantelegal' => $request->get('representantelegal'),
            'iess1' => $request->get('iess1'),
            'iess2' => $request->get('iess2'),
            'mt' => $request->get('mt'),
            'mrl' => $request->get('mrl'),
            'super' => $request->get('super'),
            'contabilidad' => $request->get('contabilidad')
        ]);

        return response()->json(['OK', 201]);
    }

    public function update(Request $request)
    {
        DB::table('cliente_auditwholes')
            ->updateOrInsert(
                ['ruc' => $request->get('ruc')],
                [
                    'razonsocial' => $request->get('razonsocial'),
                    'phone' => $request->get('phone'),
                    'mail' => $request->get('mail'),
                    'direccion' => $request->get('direccion'),
                    'diadeclaracion' => $request->get('diadeclaracion'),
                    'sri' => $request->get('sri'),
                    'representantelegal' => $request->get('representantelegal'),
                    'iess1' => $request->get('iess1'),
                    'iess2' => $request->get('iess2'),
                    'mt' => $request->get('mt'),
                    'mrl' => $request->get('mrl'),
                    'super' => $request->get('super'),
                    'contabilidad' => $request->get('contabilidad')
                ]
            );

        return response()->json(['OK', 201]);
    }
}
