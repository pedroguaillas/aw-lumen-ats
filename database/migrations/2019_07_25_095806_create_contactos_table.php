<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contactos', function (Blueprint $table) {
            $table->char('id', 13)->primary();
            $table->string('denominacion');
            $table->char('tpId', 2); //04-RUC/05-CEDULA/06-Pasaporte/07-Cliente final
            $table->char('tpContacto', 2)->nullable(); //Si tiene Cedula o RUC depende del tercer digito /02 (6,9):01 
            //Linea anterior en caso de pasaport o Cliente-final null
            $table->char('contabilidad', 2)->nullable(); //Si o No 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clientes');
    }
}