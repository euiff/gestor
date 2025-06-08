<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDuracaoToPlanosRenovacaoTable extends Migration
{
    public function up()
    {
        Schema::table('planos_renovacao', function (Blueprint $table) {
            $table->string('duracao')->after('limite')->nullable(); // Adiciona a coluna 'duracao'
        });
    }

    public function down()
    {
        Schema::table('planos_renovacao', function (Blueprint $table) {
            $table->dropColumn('duracao'); // Remove a coluna 'duracao' se a migração for revertida
        });
    }
}
