<?php
// app/Http/Controllers/UserController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
    $this->middleware('permission:visualizar-relatorios', ['only' => ['index']]);
    $this->middleware('permission:editar-perfil', ['only' => ['edit', 'update']]);
  }

  public function index()
  {
    // Exibir informações do usuário logado
    $user = Auth::user();
    return view('user.index', compact('user'));
  }

  public function edit()
  {
    // Exibir formulário para editar informações do usuário logado
    $user = Auth::user();
    return view('user.edit', compact('user'));
  }

  public function update(Request $request)
  {
    // Atualizar informações do usuário logado
    $user = Auth::user();
    $user->update($request->all());
    return redirect()->route('user.index')->with('success', 'Informações atualizadas com sucesso.');
  }
}
