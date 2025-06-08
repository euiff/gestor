<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->unsignedBigInteger('user_id');
            $table->string('whatsapp');
            $table->string('password');
            $table->date('vencimento');
            $table->unsignedBigInteger('servidor_id'); // Certifique-se de que este campo está correto
            $table->string('mac')->nullable();
            $table->boolean('notificacoes');
            $table->unsignedBigInteger('plano_id'); // Substituir o campo 'plano' por 'plano_id'
            $table->integer('numero_de_telas');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('servidor_id')->references('id')->on('servidores')->onDelete('cascade'); // Certifique-se de que a chave estrangeira está correta
            $table->foreign('plano_id')->references('id')->on('planos')->onDelete('set null'); // Adicionar a chave estrangeira para 'plano_id'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clientes');
    }
}
