<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\SalaryAdvance;

class SalaryAdvanceController extends Controller
{
    public function list(int $salary_id)
    {
        $salaryadvances = SalaryAdvance::where('salary_id', $salary_id)
            ->get();

        return response()->json([
            'salaryadvances' => $salaryadvances
        ]);
    }
}
