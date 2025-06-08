<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\PlanoRenovacao;
use App\Services\PluginService;

class PluginController extends Controller
{
    protected $pluginService;

    public function __construct(PluginService $pluginService)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (Auth::user()->role_id != 1) {
                return redirect('/app/ecommerce/dashboard')->with('error', 'Você não tem permissão para acessar esta página.');
            }
            return $next($request);
        });

        $this->pluginService = $pluginService;
    }

    public function index()
    {
        $user = Auth::user();
        $domain = request()->getHost();

        $checkResponse = $this->pluginService->checkModuleStatus($domain);

        $activeModules = [];
        if ($checkResponse && isset($checkResponse['success']) && $checkResponse['success'] && isset($checkResponse['modules'])) {
            foreach ($checkResponse['modules'] as $module) {
                $activeModules[strtolower($module)] = true;
            }
        }

        $plugins = $this->pluginService->getPlugins();
        $current_plan_id = $user->plano_id;
        $planos_revenda = PlanoRenovacao::all();

        return view('plugins.index', compact('plugins', 'current_plan_id', 'planos_revenda', 'user', 'activeModules'));
    }

    public function show($id)
    {
        // Implementar lógica para exibir detalhes do plugin, se necessário
    }

    public function initiatePurchase(Request $request)
    {
        $request->validate([
            'plugin_id' => 'required|integer',
        ]);

        $user = Auth::user();
        $domain = $request->getHost();

        $checkResponse = $this->pluginService->checkModuleStatus($domain);

        if (!$checkResponse) {
            return response()->json(['error' => 'Falha ao verificar o status do módulo.'], 500);
        }

        $activeModules = [];
        if (isset($checkResponse['success']) && $checkResponse['success'] && isset($checkResponse['modules'])) {
            foreach ($checkResponse['modules'] as $module) {
                $activeModules[$module] = true;
            }
        }

        $plugin = $this->pluginService->getPluginById($request->plugin_id);

        if (!$plugin || !isset($plugin['name'])) {
            return response()->json(['error' => 'Plugin não encontrado.'], 404);
        }

        if (isset($activeModules[strtolower($plugin['name'])])) {
            return response()->json(['error' => 'Este módulo já foi adquirido.'], 400);
        }

        Log::info('Iniciando compra para o usuário', ['user_id' => $user->id, 'plugin_id' => $request->plugin_id]);

        $response = $this->pluginService->initiatePurchase($request->plugin_id, $user->id);

        if (!$response) {
            return response()->json(['error' => 'Falha ao iniciar a compra.'], 500);
        }

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']], 400);
        }

        return response()->json($response);
    }

    public function checkPaymentStatus(Request $request)
    {
        try {
            $request->validate([
                'payment_id' => 'required|string',
                'user_id' => 'required|integer',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro na validação do request.'], 400);
        }

        try {
            $response = $this->pluginService->checkPaymentStatus($request->payment_id, $request->user_id);

            if (!$response) {
                Log::error('Falha ao verificar o status do pagamento');
                return response()->json(['error' => 'Falha ao verificar o status do pagamento.'], 500);
            }

            if (isset($response['error'])) {
                return response()->json(['error' => $response['error']], 400);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao fazer a requisição para a API.'], 500);
        }
    }
}