<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Indicacoes;
use App\Models\PlanoRenovacao;
use App\Models\CompanyDetail; // Adicione esta linha para importar o modelo CompanyDetail
use Carbon\Carbon;

class RegisterBasic extends Controller
{
    public function index()
    {
        $pageConfigs = ['myLayout' => 'blank'];
        return view('content.authentications.auth-register-basic', ['pageConfigs' => $pageConfigs]);
    }

    public function register(Request $request)
    {
        // Validação dos dados
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:users,name',
            'whatsapp' => 'required|string|max:15|unique:users,whatsapp',
            'password' => 'required|string|min:8',
            'terms' => 'accepted',
        ], [
            'name.required' => 'O campo nome é obrigatório.',
            'name.unique' => 'Este nome já está em uso.',
            'whatsapp.required' => 'O campo WhatsApp é obrigatório.',
            'whatsapp.max' => 'O campo WhatsApp não pode ter mais que 15 caracteres.',
            'whatsapp.unique' => 'Este WhatsApp já está em uso.',
            'password.required' => 'O campo senha é obrigatório.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'terms.accepted' => 'Você deve aceitar os termos e condições.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Buscar o plano Básico na tabela planos_renovacao
        $planoEssencial = PlanoRenovacao::where('nome', 'Essencial')->first();

        // Verificar se o plano Básico foi encontrado
        if (!$planoEssencial) {
            return redirect()->back()->with('error', 'Plano Essencial não encontrado.')->withInput();
        }

        // Criação do usuário com o papel "cliente" e período de teste de 7 dias
        $user = User::create([
            'name' => $request->name,
            'whatsapp' => $request->whatsapp,
            'password' => Hash::make($request->password),
            'role_id' => 2,
            'trial_ends_at' => Carbon::now()->addDays(7), // Período de teste de 7 dias
            'status' => 'ativo', // Status do usuário
            'plano_id' => $planoEssencial->id, // Associar o plano Essencial ao usuário
            'limite' => $planoEssencial->limite, // Armazenar o limite do plano na tabela users
            'creditos' => 0, // Inicializar os créditos do usuário
            'profile_photo_url' => '/assets/img/avatars/14.png', // URL da foto de perfil padrão
        ]);

        // Criar registro na tabela company_details
        CompanyDetail::create([
            'user_id' => $user->id,
            'company_name' => 'Gestor Veetv', // Usando o nome do usuário como nome da empresa inicialmente
            'company_whatsapp' => $request->whatsapp, // Usando o WhatsApp do usuário
            'company_logo' => '/assets/img/logos/ico%20ds.png', // Logo padrão
            'company_logo_dark' => '/assets/img/logos/logo-dark.png',
            'company_logo_light' => '/assets/img/logos/logo-light.png', // Pode ser preenchido depois pelo usuário
            'favicon' => '/assets/img/favicons/favico.png', // Pode ser preenchido depois pelo usuário
        ]);

        // Criar uma entrada na tabela indicacoes se houver uma referência
        if ($request->has('ref') && !empty($request->ref)) {
            Indicacoes::create([
                'user_id' => $request->ref,
                'referred_id' => $user->id,
                'status' => 'pendente', // Status inicial da indicação
                'ganhos' => 0.00,
            ]);
        }

        // Autenticar o usuário
        auth()->login($user);

        // Redirecionar para o dashboard do cliente
        return redirect()->route('app-ecommerce-dashboard');
    }
}