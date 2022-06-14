<?php

namespace App\Http\Controllers;

use App\ClienteAuditwhole;
use Illuminate\Http\Request;
use App\Http\Resources\PaymentResources;
use App\Payment;

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

        $payments = Payment::where('cliente_auditwhole_ruc', $ruc)
            ->orderBy('month', 'DESC');

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
}
