<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotGatewayToCompanyDetailsTable extends Migration
{
    public function up()
    {
        Schema::table('company_details', function (Blueprint $table) {
            $table->boolean('not_gateway')->default(false)->after('evolution_api_key');
        });
    }

    public function down()
    {
        Schema::table('company_details', function (Blueprint $table) {
            $table->dropColumn('not_gateway');
        });
    }
}
