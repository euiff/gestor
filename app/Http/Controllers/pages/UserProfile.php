<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\PlanoRenovacao;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UserProfile extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
  }

  public function index()
  {
    $user = auth()->user();
    $planos = PlanoRenovacao::all();
    $planos_revenda = PlanoRenovacao::all();
    $current_plan_id = $user->plano_id;

    return view('content.pages.pages-profile-user', compact('planos','planos_revenda', 'current_plan_id'));
  }

  public function update(Request $request)
  {
    $user = auth()->user();

    \Log::info('Iniciando atualização do perfil para o usuário: ' . $user->id);

    try {
        $request->validate([
          'name' => 'required|string|max:255',
          'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
          'whatsapp' => 'nullable|string|max:255|unique:users,whatsapp,' . $user->id,
          'password' => 'nullable|string|min:8',
          'two_factor' => 'required|boolean',
        ]);

        \Log::info('Validação concluída com sucesso.');

        $user->name = $request->name;
        $user->whatsapp = $request->whatsapp;

        if ($request->filled('password')) {
          $user->password = Hash::make($request->password);
          \Log::info('Senha atualizada.');
        }

        if ($request->hasFile('profile_photo')) {
          // Define permissões 777 na pasta
          $directory = public_path('assets/img/avatars');
          if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
            \Log::info('Diretório criado: ' . $directory);
          }
          chmod($directory, 0777);
          \Log::info('Permissões definidas para o diretório: ' . $directory);

          // Delete old profile photo if exists
          if ($user->profile_photo_url) {
            $oldPhotoPath = public_path($user->profile_photo_url);
            if (file_exists($oldPhotoPath)) {
              unlink($oldPhotoPath);
              \Log::info('Foto de perfil antiga deletada.');
            }
          }

          // Store new profile photo
          $fileName = $request->file('profile_photo')->getClientOriginalName();
          $path = $request->file('profile_photo')->move($directory, $fileName);
          if ($path) {
            $user->profile_photo_url = '/assets/img/avatars/' . $fileName; // Salva o caminho relativo no banco de dados
            \Log::info('Nova foto de perfil armazenada em: ' . $user->profile_photo_url);
          } else {
            \Log::error('Falha ao armazenar a nova foto de perfil.');
          }
        }

        // Lógica para ativar ou desativar o 2FA
        if ($request->two_factor) {
          if (!$user->two_factor_secret) {
            // Gerar e salvar o segredo do 2FA
            $user->two_factor_secret = Str::random(16);
            \Log::info('2FA ativado para o usuário: ' . $user->id);
          }
        } else {
          $user->two_factor_secret = null;
          \Log::info('2FA desativado para o usuário: ' . $user->id);
        }

        $user->save();
        \Log::info('Perfil atualizado com sucesso.');

        return redirect()->back()->with('success', 'Perfil atualizado com sucesso.');
    } catch (\Exception $e) {
        \Log::error('Erro ao atualizar perfil: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Erro ao atualizar perfil.');
    }
  }

  public function verificarValidadePainel()
  {
      $user = auth()->user();
      $trialEndsAt = Carbon::parse($user->trial_ends_at);
      $daysRemaining = $trialEndsAt->diffInDays(Carbon::now());
      $isExpired = Carbon::now()->greaterThan($trialEndsAt);

      if ($isExpired || $daysRemaining <= 7) {
          return redirect()->route('planos')->with('warning', 'Seu painel está vencido ou prestes a vencer. Por favor, contrate um dos nossos planos.');
      }

      return redirect()->route('dashboard')->with('success', 'Seu painel está válido.');
  }
}