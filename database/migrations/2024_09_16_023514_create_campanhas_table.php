<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampanhasTable extends Migration
{
    public function up()
    {
        Schema::create('campanhas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Dono da campanha
            $table->string('nome');
            $table->time('horario');
            $table->json('contatos')->nullable();
            $table->string('origem_contatos');
            $table->boolean('ignorar_contatos');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('campanhas');
    }
}
