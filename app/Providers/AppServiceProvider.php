<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helpers\EncryptedFileLoader;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        try {
            // Carregar e avaliar os arquivos de serviço criptografados
            EncryptedFileLoader::load(app_path('Services/PluginService.php.pix'));
            EncryptedFileLoader::load(app_path('Services/ModuleStatusService.php.pix'));
            EncryptedFileLoader::load(app_path('Services/TrialService.php.pix'));
            EncryptedFileLoader::load(app_path('Services/LicenseService.php.pix'));

            // Registrar os serviços como singletons
            $this->app->singleton(\App\Services\PluginService::class, function ($app) {
                return new \App\Services\PluginService();
            });

            $this->app->singleton(\App\Services\ModuleStatusService::class, function ($app) {
                return new \App\Services\ModuleStatusService();
            });

            $this->app->singleton(\App\Services\TrialService::class, function ($app) {
                return new \App\Services\TrialService();
            });

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Por favor, contate o suporte.');
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}