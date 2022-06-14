<?php

namespace App\Http\Controllers;

use App\ClienteAuditwhole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ClienteAuditwholeResources;
use App\Payment;
use App\User;

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
            ->where('rol', 'LIKE', 'asesor')
            ->where(function ($query) use ($search) {
                return $query->where('ruc', 'LIKE', "%$search%")
                    ->orWhere('razonsocial', 'LIKE', "%$search%");
            })
            ->orderBy('cliente_auditwholes.created_at', 'DESC');

        return ClienteAuditwholeResources::collection($customers->paginate($paginate));
    }

    public function store(Request $request)
    {
        try {
            $custom = ClienteAuditwhole::create($request->all());

            return response()->json(['custom' => $custom]);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return response()->json(['message' => 'KEY_DUPLICATE'], 405);
            }
        }
    }

    public function show($ruc)
    {
        $custom = ClienteAuditwhole::where('ruc', $ruc)->first();

        return response()->json([
            'custom' => $custom,
            'user' =>  User::find($custom->user_id)
        ]);
    }

    public function update(string $ruc, Request $request)
    {
        try {
            $result = DB::table('cliente_auditwholes')
                ->updateOrInsert(
                    ['ruc' => $ruc],
                    [
                        'razonsocial' => $request->get('razonsocial'),
                        'sri' => $request->get('sri'),
                        'amount' => $request->get('amount'),
                        'user_id' => $request->get('user_id'),
                        'updated_at' => date('Y-m-d H:i:s', strtotime('+5 hours'))
                    ]
                );

            return response()->json(['result' => $result]);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return response()->json(['message' => 'KEY_DUPLICATE'], 405);
            }
        }
    }

    public function payments(string $ruc)
    {
        $payments = Payment::where('cliente_auditwhole_ruc', $ruc)
            ->orderBy('month', 'DESC')
            ->get();

        $month = null;
        $year = null;

        if (count($payments)) {
            $payment = $payments->first();
            if ((int)$payment->month === 12) {
                $month = 1;
                $year = $payment->year + 1;
            } else {
                $month = $payment->month + 1;
                $year = $payment->year;
            }
        } else {
            $date = strtotime("-1 month");
            $month = (int)date('m', $date);
            $year = date('Y');
            if ($month === 1) {
                $year++;
            }
        }

        return response()->json([
            'payments' => $payments,
            'customer' => ClienteAuditwhole::where('ruc', $ruc)->first(),
            'year' => $year,
            'month' => $month,
        ]);
    }
}
