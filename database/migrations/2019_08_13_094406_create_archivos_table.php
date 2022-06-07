<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArchivosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('archivos', function (Blueprint $table) {
            //$table->bigIncrements('id');
            $table->string('cliente_auditwhole_ruc');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->longText('filecompra')->nullable();
            $table->longText('fileventa')->nullable();
            $table->mediumText('fileanulado')->nullable();

            $table->primary(['cliente_auditwhole_ruc', 'mes', 'anio']);
            $table->foreign('cliente_auditwhole_ruc')->references('ruc')->on('cliente_auditwholes');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('archivos');
    }
}
