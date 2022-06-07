<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClienteAuditwhole extends Model
{
    // Test before uncommmented

    // protected $primaryKey = 'ruc';

    protected $fillable = [
        'ruc', 'user_id', 'razonsocial', 'nombrecomercial',
        'phone', 'mail', 'direccion', 'diadeclaracion', 'sri',
        'representantelegal', 'iess1', 'iess2', 'mt', 'mrl', 'super',
        'contabilidad', 'amount'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
