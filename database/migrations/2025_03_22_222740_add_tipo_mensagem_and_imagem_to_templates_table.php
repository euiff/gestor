<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up()
{
    Schema::table('templates', function (Blueprint $table) {
        $table->string('tipo_mensagem')->default('texto')->after('conteudo'); // Padrão é 'texto'
        $table->string('imagem')->nullable()->after('tipo_mensagem'); // Caminho da imagem (opcional)
    });
}

public function down()
{
    Schema::table('templates', function (Blueprint $table) {
        $table->dropColumn('tipo_mensagem');
        $table->dropColumn('imagem');
    });
}
};
