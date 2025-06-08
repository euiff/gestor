<?php
// app/Http/Controllers/AdminController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
    $this->middleware('permission:gerenciar-usuarios', ['only' => ['index', 'edit', 'update', 'destroy']]);
  }

  public function index()
  {
    // Exibir lista de usuários
    $users = User::all();
    return view('admin.index', compact('users'));
  }

  public function edit($id)
  {
    // Exibir formulário para editar informações do usuário
    $user = User::findOrFail($id);
    return view('admin.edit', compact('user'));
  }

  public function update(Request $request, $id)
  {
    // Atualizar informações do usuário
    $user = User::findOrFail($id);
    $user->update($request->all());
    return redirect()->route('admin.index')->with('success', 'Informações do usuário atualizadas com sucesso.');
  }

  public function destroy($id)
  {
    // Excluir usuário
    $user = User::findOrFail($id);
    $user->delete();
    return redirect()->route('admin.index')->with('success', 'Usuário excluído com sucesso.');
  }
}