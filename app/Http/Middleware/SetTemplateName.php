<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\CompanyDetail;
use App\Models\User;
use App\Models\Cliente;

class SetTemplateName
{
    public function handle($request, Closure $next)
    {
        // Verificar se o usuário está autenticado
        if (Auth::check()) {
            $user = Auth::user();
            // Log::info('Usuário autenticado:', ['user' => $user]);

            // Verificar se o usuário é um cliente
            if ($user->role_id == 3) { 
                $cliente = Cliente::where('id', $user->id)->first();
                if ($cliente) {
                    // Log::info('Cliente encontrado:', ['cliente' => $cliente]);
                    $companyDetails = CompanyDetail::where('user_id', $cliente->user_id)->first();
                    // Log::info('Detalhes da empresa para o cliente:', ['companyDetails' => $companyDetails]);
                } else {
                    // Log::info('Cliente não encontrado para o usuário:', ['user_id' => $user->id]);
                }
            } else {
                $companyDetails = CompanyDetail::where('user_id', $user->id)->first();
                // Log::info('Detalhes da empresa para o usuário:', ['companyDetails' => $companyDetails]);
            }

            if (isset($companyDetails) && $companyDetails) {
                config(['variables.templateName' => $companyDetails->company_name]);
                config(['variables.favicon' => $companyDetails->favicon ?? 'assets/img/favicon/favicon.ico']);
                // Log::info('Nome da empresa e favicon definidos:', ['templateName' => $companyDetails->company_name, 'favicon' => $companyDetails->favicon]);
            } else {
                config(['variables.templateName' => 'Gestor Veetv']);
                config(['variables.favicon' => 'assets/img/favicon/favicon.ico']);
                // Log::info('Nome da empresa e favicon padrão definidos');
            }
        } else {
            // Buscar o user_id do usuário com role_id = 1
            $defaultUser = User::where('role_id', 1)->first();
            if ($defaultUser) {
                $companyDetails = CompanyDetail::where('user_id', $defaultUser->id)->first();
                if ($companyDetails) {
                    config(['variables.templateName' => $companyDetails->company_name]);
                    config(['variables.favicon' => $companyDetails->favicon ?? 'assets/img/favicon/favicon.ico']);
                    // Log::info('Nome da empresa e favicon padrão definidos a partir do usuário com role_id = 1');
                } else {
                    config(['variables.templateName' => 'Gestor Veetv']);
                    config(['variables.favicon' => 'assets/img/favicon/favicon.ico']);
                    // Log::info('Nome da empresa e favicon padrão definidos');
                }
            } else {
                config(['variables.templateName' => 'Gestor Veetv']);
                config(['variables.favicon' => 'assets/img/favicon/favicon.ico']);
                // Log::info('Nome da empresa e favicon padrão definidos');
            }
        }

        return $next($request);
    }
}