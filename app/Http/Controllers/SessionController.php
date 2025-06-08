<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use App\Models\User;
use App\Models\PlanoRenovacao;
use App\Models\CompanyDetail;

class SessionController extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de autenticação
        $this->middleware('auth');
    }

    public function index()
    {
        if (Auth::check()) {
            // Usuário está autenticado
            $user = Auth::user();
            $userRole = $user->role->name;

            if ($userRole === 'admin') {
                // Buscar todas as sessões no banco de dados
                $sessions = DB::table('sessions')
                    ->join('company_details', 'sessions.user_id', '=', 'company_details.user_id')
                    ->select('sessions.*', 'company_details.api_session')
                    ->get();

                $planos_revenda = PlanoRenovacao::all();
                $current_plan_id = $user->plano_id;


                // Inicializar o cliente HTTP
                $client = new Client();

                // Iterar sobre as sessões e buscar a localização para cada endereço IP
                foreach ($sessions as $session) {
                    $response = $client->get('http://ipinfo.io/' . $session->ip_address . '/json', [
                        'query' => [
                            'token' => $session->api_session // Substitua pela sua chave de API
                        ]
                    ]);

                    $locationData = json_decode($response->getBody(), true);

                    // Verificar se as chaves existem antes de acessá-las
                    $city = $locationData['city'] ?? 'Desconhecido';
                    $region = $locationData['region'] ?? 'Desconhecido';
                    $country = $locationData['country'] ?? 'Desconhecido';


                    $session->location = $city . ', ' . $region . ', ' . $country;
                    $user = User::find($session->user_id);
                    $session->user_name = $user ? $user->name : 'Usuário Desconhecido';
                  }

                // Retornar a view com as sessões
                return view('admin.sessions.index', compact('sessions', 'planos_revenda', 'current_plan_id'));
            } else {
            // Redirecionar para a página de login se o usuário não estiver autenticado
            return redirect()->route('auth-login-basic');
        }
    }
}
}
