<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    protected $fillable = [
        'id', 'denominacion', 'tpId', 'tpContacto', 'contabilidad'
    ];
}
