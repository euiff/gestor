<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTemplatesTableCharset extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('templates', function (Blueprint $table) {
            // Alterar a collation da coluna 'conteudo' para utf8mb4
            $table->text('conteudo')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('templates', function (Blueprint $table) {
            // Reverter a collation da coluna 'conteudo' para utf8
            $table->text('conteudo')->charset('utf8')->collation('utf8_unicode_ci')->change();
        });
    }
}