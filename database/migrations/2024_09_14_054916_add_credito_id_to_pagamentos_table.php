<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreditoIdToPagamentosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pagamentos', function (Blueprint $table) {
            $table->unsignedBigInteger('credito_id')->nullable()->after('status');

            // Se você quiser adicionar uma chave estrangeira
            // $table->foreign('credito_id')->references('id')->on('creditos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pagamentos', function (Blueprint $table) {
            $table->dropColumn('credito_id');

            // Se você adicionou uma chave estrangeira
            // $table->dropForeign(['credito_id']);
        });
    }
}
