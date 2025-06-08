<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('company_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('company_name');
            $table->string('company_whatsapp');
            $table->string('access_token')->nullable(); // Adiciona o campo access_token
            $table->string('company_logo')->nullable(); // Adiciona o campo company_logo
            $table->string('pix_manual')->nullable(); // Adiciona o campo pix_manual
            $table->decimal('referral_balance', 8, 2)->nullable(); // Adiciona o campo referral_balance
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_details');
    }
}
