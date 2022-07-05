<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\SalaryResources;
use App\Salary;
use App\User;

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

        $salaries = Salary::where('user_id', $user_id)
            ->orderBy('month', 'DESC')
            ->get();

        $month = null;
        $year = null;

        if (count($salaries)) {
            $salary = $salaries->first();
            if ((int)$salary->month === 12) {
                $month = 1;
                $year = $salary->year + 1;
            } else {
                $month = $salary->month + 1;
                $year = $salary->year;
            }
        } else {
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
            'month' => $month,
        ]);
    }

    public function store(Request $request)
    {
        $salary = Salary::where([
            'user_id' => $request->user_id,
            'month' => $request->month,
            'year' => $request->year,
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
