<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLimiteToPlanosRenovacaoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('planos_renovacao', function (Blueprint $table) {
            $table->integer('limite')->default(0)->after('botao'); // Adiciona a coluna 'limite' com valor padrão 0
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('planos_renovacao', function (Blueprint $table) {
            $table->dropColumn('limite');
        });
    }
}
