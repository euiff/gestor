<?php

namespace App\Http\Controllers\apps;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\PlanoRenovacao;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;
use Illuminate\Support\Facades\Log;

class EcommerceSettingsPayments extends Controller
{
  public function index()
  {
    $user = Auth::user();

    $planos = Plano::all();
    $planos_revenda = PlanoRenovacao::all();
    $current_plan_id = $user->plano_id;
    return view('content.apps.app-ecommerce-settings-payments', compact('planos', 'planos_revenda', 'current_plan_id'));
  }

  public function uploadModulo(Request $request)
  {

      $request->validate([
          'modulo' => 'required|mimes:zip',
      ]);

      $file = $request->file('modulo');
      $fileName = time() . '_' . $file->getClientOriginalName();
      $filePath = storage_path('app/' . $fileName);

     
      // Move o arquivo para o diretório de armazenamento
      $file->move(storage_path('app'), $fileName);
    
      // Garantir que a pasta campanhas tenha as permissões corretas
      $campanhasPath = resource_path('views/campanhas');
      if (!File::exists($campanhasPath)) {
          File::makeDirectory($campanhasPath, 0777, true, true);
        
      } else {
      }

      // Descompactar o arquivo diretamente na pasta campanhas
      $zip = new ZipArchive;
      if ($zip->open($filePath) === TRUE) {
          $zip->extractTo($campanhasPath);
          $zip->close();
         
      } else {
        
          return back()->with('error', 'Falha ao descompactar o arquivo.');
      }

      // Deletar o arquivo ZIP após a extração
      try {
          File::delete($filePath);
         
      } catch (\Exception $e) {
        
          return back()->with('error', 'Erro ao deletar o arquivo ZIP.');
      }

      // Executar migrações
      try {
          Artisan::call('migrate');
          
      } catch (\Exception $e) {
         
          return back()->with('error', 'Erro ao executar migrações.');
      }

     
      return back()->with('success', 'Módulo instalado com sucesso!');
  }
}
