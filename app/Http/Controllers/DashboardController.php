<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $customer = DB::table('cliente_auditwholes')->select(DB::raw("COUNT(*) as count"))
            ->first();

        $user = DB::table('users')->select(DB::raw("COUNT(*) as count"))
            ->where('rol', 'asesor')
            ->first();

        $payment = DB::table('payments')->select(DB::raw("SUM(amount) as amount"))
            ->first();

        $payments = DB::table('payments')->select(DB::raw("SUM(amount) as amount, MONTH(date) AS month1, YEAR(date) AS year1"))
            ->whereNotNull('date')
            ->whereYear('date', 2022)
            ->groupBy('month1', 'year1')->get();

        $payment_types = DB::table('payments')->select(DB::raw("SUM(amount) as amount, type"))
            ->groupBy('type')->get();

        return response()->json([
            'total_customers' => $customer->count,
            'total_users' => $user->count,
            'total_payments' => number_format($payment->amount, 2),
            'payment_months' => $payments,
            'payment_types' => $payment_types
        ]);
    }
}
