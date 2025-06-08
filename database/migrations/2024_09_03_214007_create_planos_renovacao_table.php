<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanosRenovacaoTable extends Migration
{
    public function up()
    {
        Schema::create('planos_renovacao', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('preco', 10, 2); // Ajuste o tamanho se necessário
            $table->text('detalhes')->nullable(); // Adiciona a coluna detalhes
            $table->string('botao')->nullable(); // Adiciona a coluna botao
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('planos_renovacao');
    }
}
