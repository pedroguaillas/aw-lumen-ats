<?php

namespace App\Http\Controllers;

use App\ClienteAuditwhole;
use Illuminate\Http\Request;
use App\Http\Resources\PaymentResources;
use App\Payment;
use App\User;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function paymentlist(Request $request)
    {
        $ruc = null;
        $paginate = 15;

        if ($request) {
            $ruc = $request->ruc;
            $paginate = $request->has('paginate') ? $request->paginate : $paginate;
        }

        $payments = Payment::where('cliente_auditwhole_ruc', $ruc)
            ->orderBy('created_at', 'DESC')
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

        $payments = Payment::where('cliente_auditwhole_ruc', $ruc)
            ->orderBy('created_at', 'DESC');

        return response()->json([
            'payments' => PaymentResources::collection($payments->paginate($paginate)),
            'customer' => ClienteAuditwhole::where('ruc', $ruc)->first(),
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function store(Request $request)
    {
        $payment = Payment::where([
            'cliente_auditwhole_ruc' => $request->cliente_auditwhole_ruc,
            'month' => $request->month,
            'year' => $request->year
        ])->get();

        if (count($payment) > 0) {
            return response()->json(['msm' => 'Ya existe pago de ese mes'], 405);
        }

        $payment = Payment::create($request->all());

        return response()->json(['payment' => $payment]);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);
        $payment->update($request->all());

        return response()->json(['payment' => $payment]);
    }

    public function destroy($id)
    {
        $payment = Payment::find($id);
        $payment->delete();
    }

    public function listtablebyuser(Request $request)
    {
        $sql = '';
        $months = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        foreach ($months as $key => $value) {
            $sql .= "(SELECT amount FROM payments WHERE cliente_auditwhole_ruc = ruc AND year = $request->year AND month = $key) AS $value, ";
        }
        // $sql = substr($sql, 0, -1);
        $sql .= "(SELECT SUM(amount) FROM payments WHERE cliente_auditwhole_ruc = ruc AND year = $request->year) AS total";

        $customers = DB::table('cliente_auditwholes')
            ->select('razonsocial', 'ruc', DB::raw($sql))
            ->where('user_id', $request->id)
            ->get();

        $user = User::find($request->id);

        return response()->json(['customers' => $customers, 'user' => $user]);
    }
}
