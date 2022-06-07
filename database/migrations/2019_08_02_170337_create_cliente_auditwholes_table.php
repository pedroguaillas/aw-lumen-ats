<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClienteAuditwholesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cliente_auditwholes', function (Blueprint $table) {
            $table->string('ruc', 13)->unique();
            $table->tinyInteger('user_id')->unsigned();
            $table->string('razonsocial');
            $table->string('nombrecomercial')->nullable();
            $table->string('phone')->nullable();
            $table->string('mail')->unique()->nullable();
            $table->string('direccion')->nullable();
            $table->tinyInteger('diadeclaracion')->nullable();
            $table->string('sri')->nullable();
            $table->string('representantelegal')->nullable();
            $table->string('iess1')->nullable();
            $table->string('iess2')->nullable();
            $table->string('mt')->nullable();
            $table->string('mrl')->nullable();
            $table->string('super')->nullable();
            $table->char('contabilidad', 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cliente_auditwholes');
    }
}
