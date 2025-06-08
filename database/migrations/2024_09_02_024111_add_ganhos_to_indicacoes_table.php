<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGanhosToIndicacoesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('indicacoes', function (Blueprint $table) {
            $table->decimal('ganhos', 8, 2)->default(0)->after('status'); // Adiciona a coluna ganhos
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('indicacoes', function (Blueprint $table) {
            $table->dropColumn('ganhos');
        });
    }
}
