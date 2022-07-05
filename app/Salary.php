<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'month', 'year', 'amount', 'cheque', 'amount_cheque', 'balance'
    ];
}
