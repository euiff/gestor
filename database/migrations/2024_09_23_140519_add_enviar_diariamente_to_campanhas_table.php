<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnviarDiariamenteToCampanhasTable extends Migration
{
    public function up()
    {
        Schema::table('campanhas', function (Blueprint $table) {
            $table->boolean('enviar_diariamente')->default(false);
        });
    }

    public function down()
    {
        Schema::table('campanhas', function (Blueprint $table) {
            $table->dropColumn('enviar_diariamente');
        });
    }
}