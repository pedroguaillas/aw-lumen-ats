<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'ruc', 'mes', 'anio', 'filecompra', 'fileventa', 'fileanulado'
    ];
}
