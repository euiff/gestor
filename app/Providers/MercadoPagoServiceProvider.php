<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\CompanyDetail;
use App\Models\User;
use Illuminate\Support\Facades\Config;

class MercadoPagoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $adminUser = User::where('role_id', 1)->first();
        if ($adminUser) {
            $companyDetail = CompanyDetail::where('user_id', $adminUser->id)->first();
            if ($companyDetail) {
                Config::set('mercado_pago.public_key', $companyDetail->public_key);
                Config::set('mercado_pago.access_token', $companyDetail->access_token);
                Config::set('mercado_pago.site_id', $companyDetail->site_id ?? 'MLB');
            }
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
