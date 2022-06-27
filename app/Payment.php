<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'cliente_auditwhole_ruc', 'month', 'year', 'amount', 'note', 'type', 'voucher', 'date'
    ];
}
