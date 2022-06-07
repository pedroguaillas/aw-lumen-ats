<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\ClienteAuditwhole;
use App\Payment;
use App\User;

class DashboardController extends Controller
{
    public function index()
    {
        $customers = ClienteAuditwhole::all();
        $users = User::where('rol', 'asesor')->get();
        $payments = DB::table('payments')->select(DB::raw("SUM(amount) as amount"))
            ->first();

        return response()->json([
            'total_customers' => count($customers),
            'total_users' => count($users),
            'total_payments' => number_format($payments->amount, 2)
        ]);
    }
}
