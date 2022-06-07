<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('contactos')->insert([
            'id' => '9999999999999',
            'denominacion' => 'CONSUMIDOR FINAL',
            'tpId' => '07',
        ]);
    }
}
