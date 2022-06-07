<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClienteAuditwholesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('cliente_auditwholes')->insert([
            'ruc' => '1105167694001',
            'user_id' => 1,
            'razonsocial' => 'Pedro Guaillas'
        ]);
    }
}
