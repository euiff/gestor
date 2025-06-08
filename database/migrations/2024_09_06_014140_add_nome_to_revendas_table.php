<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNomeToRevendasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('revendas', function (Blueprint $table) {
            $table->string('nome')->after('id'); // Adiciona a coluna 'nome' após a coluna 'id'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('revendas', function (Blueprint $table) {
            $table->dropColumn('nome');
        });
    }
}
