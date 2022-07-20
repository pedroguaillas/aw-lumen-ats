<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\SalaryResources;
use App\Salary;
use App\User;
use Illuminate\Support\Facades\DB;

class SalaryController extends Controller
{
    public function salarylist(Request $request)
    {
        $user_id = null;
        $paginate = 15;

        if ($request) {
            $user_id = $request->user_id;
            $paginate = $request->has('paginate') ? $request->paginate : $paginate;
        }

        $salaries = DB::table('salaries AS s')
            ->select('s.id', 's.user_id', 's.month', 's.amount', 's.cheque', 's.amount_cheque', 's.balance', DB::raw('SUM(salary_advances.amount) AS paid'))
            ->leftJoin('salary_advances', 's.id', 'salary_id')
            ->where('user_id', $user_id)
            ->groupBy('s.id', 's.user_id', 's.month', 's.amount', 's.cheque', 's.amount_cheque', 's.balance')
            ->orderBy('month', 'DESC')
            ->get();

        $month = null;
        $year = null;

        // Le suma un mes al ultimo mes de pago
        if (count($salaries)) {
            $salary = $salaries->first();
            $month = (int)substr($salary->month, 5, 2);
            $year = (int)substr($salary->month, 0, 4);
            if ($month === 12) {
                $month = 1;
                $year++;
            } else {
                $month++;
            }
        } else {
            // Signfica que es el primer pago, entonces se paga el mes anterior
            $date = strtotime("-1 month");
            $month = (int)date('m', $date);
            $year = date('Y');
        }

        $salaries = Salary::where('user_id', $user_id)
            ->orderBy('month', 'DESC');

        return response()->json([
            'salaries' => SalaryResources::collection($salaries->paginate($paginate)),
            'user' => User::find($user_id),
            'year' => $year,
            'month' => $month
        ]);
    }

    public function store(Request $request)
    {
        $salary = Salary::where([
            'user_id' => $request->user_id,
            'month' => $request->month
        ])->get();

        if (count($salary) > 0) {
            return response()->json(['msm' => 'Ya existe sueldo de ese mes'], 405);
        }

        $salary = Salary::create($request->all());

        return response()->json(['salary' => $salary]);
    }

    public function update(Request $request, $id)
    {
        $salary = Salary::find($id);
        $salary->update($request->all());

        return response()->json(['salary' => $salary]);
    }

    public function destroy($id)
    {
        $salary = Salary::find($id);
        $salary->delete();
    }
}
