<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('conexoes', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id'); // ID do usuário
      $table->text('qrcode')->nullable(); // QR Code (ajustado para text)
      $table->boolean('conn')->default(0); // Conexão ativa (0 = inativa, 1 = ativa)
      $table->string('whatsapp')->unique(); // Número do WhatsApp
      $table->timestamp('data_cadastro')->useCurrent(); // Data de cadastro
      $table->timestamp('data_alteracao')->nullable()->useCurrentOnUpdate(); // Data de alteração
      $table->string('tokenid')->nullable(); // Token ID
      $table->boolean('notifica')->default(0); // Notificação
      $table->string('saudacao')->nullable(); // Saudação
      $table->string('arquivo')->nullable(); // Arquivo
      $table->string('midia')->nullable(); // Mídia
      $table->string('tipo')->nullable(); // Tipo
      $table->timestamps();

      // Definindo a chave estrangeira
      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('conexoes');
  }
};
