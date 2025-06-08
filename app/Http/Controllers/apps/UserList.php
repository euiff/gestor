<?php

namespace App\Http\Controllers\apps;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PlanoRenovacao;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Log;

class UserList extends Controller
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
            $userId = $user->id;
            $userRole = $user->role->name;

            // Verificar se o usuário é administrador
            if ($userRole === 'admin') {
                // Buscar todos os usuários
                $users = User::with(['role', 'plano', 'parent'])->get();
                $planos_revenda = PlanoRenovacao::all();
                $current_plan_id = $user->plano_id;
                return view('content.apps.app-user-list', compact('users', 'planos_revenda', 'current_plan_id'));
            } else {
                // Redirecionar ou mostrar mensagem de erro se não for administrador
                return redirect()->route('app-ecommerce-dashboard')->with('error', 'Você não tem permissão para acessar esta página.');
            }
        } else {
            // Redirecionar para a página de login se o usuário não estiver autenticado
            return redirect()->route('auth-login-basic');
        }
    }

       public function list(Request $request)
    {
        Log::info('Acessando a listagem de usuários com paginação e busca.');
    
        try {
            if (Auth::check()) {
                $user = Auth::user();
                $userRole = $user->role->name;
    
                $search = $request->input('search');
                $sort = $request->input('sort', 'id');
                $order = $request->input('order', 'DESC');
    
                // Verifica se o usuário é um administrador
                if ($userRole === 'admin') {
                    $filter = $request->input('filter', 'all');
    
                    if ($filter == 'mine') {
                        // Mostrar apenas os usuários do administrador
                        $users = User::where('user_id', $user->id);
                    } else {
                        // Mostrar todos os usuários
                        $users = User::query();
                    }
                } else {
                    // Retorna apenas os dados do usuário logado se não for administrador
                    $users = User::where('user_id', $user->id);
                }
    
                if ($search) {
                    $users = $users->where('name', 'like', '%' . $search . '%');
                }
    
                $totalUsers = $users->count();
                $canEdit = true; // Defina a lógica para verificar se o usuário pode editar
                $canDelete = true; // Defina a lógica para verificar se o usuário pode deletar
    
                $planos_revenda = PlanoRenovacao::all(); // Certifique-se de buscar os planos de revenda
    
                $users = $users->orderBy($sort, $order)
                    ->paginate($request->input('limit', 10))
                    ->through(function ($user) use ($canEdit, $canDelete, $planos_revenda) {
                        $actions = '<div class="d-grid gap-3">
                                        <div class="row g-3">
                                            <div class="col-4 mb-2">
                                                <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#editUser' . $user->id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                            <div class="col-4 mb-2">
                                                <button class="btn btn-sm btn-warning w-100" data-bs-toggle="modal" data-bs-target="#renewUserModal' . $user->id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Renovar">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </div>
                                            <div class="col-4 mb-2">
                                                <form action="' . route('users.destroy', $user->id) . '" method="POST" style="display:inline;">
                                                    ' . csrf_field() . '
                                                    ' . method_field('DELETE') . '
                                                    <button type="submit" class="btn btn-sm btn-danger w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Deletar">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>';
    
                        $modalEdit = '<div class="modal fade" id="editUser' . $user->id . '" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-simple modal-edit-user">
                                            <div class="modal-content p-3 p-md-5">
                                                <div class="modal-body">
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    <div class="text-center mb-4">
                                                        <h3 class="mb-2">Editar Usuário</h3>
                                                        <p class="text-muted">Atualize os detalhes do usuário.</p>
                                                    </div>
                                                    <form id="editUserForm' . $user->id . '" class="row g-3" action="' . route('users.update', $user->id) . '" method="POST">
                                                        ' . csrf_field() . '
                                                        ' . method_field('PUT') . '
                                                        <div class="col-12">
                                                            <label class="form-label" for="editUserName' . $user->id . '">Nome</label>
                                                            <input type="text" id="editUserName' . $user->id . '" name="name" class="form-control" value="' . $user->name . '" required />
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label" for="editUserWhatsapp' . $user->id . '">WhatsApp</label>
                                                            <input type="text" id="editUserWhatsapp' . $user->id . '" name="whatsapp" class="form-control" value="' . $user->whatsapp . '" required />
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label" for="editUserRole' . $user->id . '">Role</label>
                                                            <select id="editUserRole' . $user->id . '" name="role_id" class="form-select">
                                                                <option value="1" ' . ($user->role_id == 1 ? 'selected' : '') . '>Admin</option>
                                                                <option value="2" ' . ($user->role_id == 2 ? 'selected' : '') . '>Master</option>
                                                                <option value="3" ' . ($user->role_id == 3 ? 'selected' : '') . '>Cliente</option>
                                                                <option value="4" ' . ($user->role_id == 4 ? 'selected' : '') . '>Revendedor</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label" for="editUserStatus' . $user->id . '">Status</label>
                                                            <select id="editUserStatus' . $user->id . '" name="status" class="form-control" required>
                                                                <option value="ativo" ' . ($user->status == 'ativo' ? 'selected' : '') . '>Ativo</option>
                                                                <option value="desativado" ' . ($user->status == 'desativado' ? 'selected' : '') . '>Desativado</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label" for="editUserPlan' . $user->id . '">Plano</label>
                                                            <select id="editUserPlan' . $user->id . '" name="plano_id" class="form-control" required onchange="updateLimite(' . $user->id . ')">
                                                                ' . $planos_revenda->map(function ($plano) use ($user) {
                                                                    return '<option value="' . $plano->id . '" data-limite="' . $plano->limite . '" ' . ($user->plano_id == $plano->id ? 'selected' : '') . '>' . $plano->nome . '</option>';
                                                                })->implode('') . '
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label" for="editUserLimite' . $user->id . '">Limite</label>
                                                            <input type="number" id="editUserLimite' . $user->id . '" name="limite" class="form-control" value="' . $user->limite . '" required />
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label" for="editUserCreditos' . $user->id . '">Créditos</label>
                                                            <input type="number" id="editUserCreditos' . $user->id . '" name="creditos" class="form-control" value="' . $user->creditos . '" required />
                                                        </div>
                                                        <div class="col-12 text-center">
                                                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Salvar</button>
                                                            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
    
                        $modalRenew = '<div class="modal fade" id="renewUserModal' . $user->id . '" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-simple modal-renew-user">
                                            <div class="modal-content p-3 p-md-5">
                                                <div class="modal-body">
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    <div class="text-center mb-4">
                                                        <h3 class="mb-2">Renovar Usuário</h3>
                                                        <p class="text-muted">Atualize os detalhes da renovação do usuário.</p>
                                                    </div>
                                                    <form id="renewUserForm' . $user->id . '" class="row g-3" action="' . route('users.renew', $user->id) . '" method="POST">
                                                        ' . csrf_field() . '
                                                        <div class="col-12">
                                                            <label class="form-label" for="renewUserStatus' . $user->id . '">Status</label>
                                                            <select id="renewUserStatus' . $user->id . '" name="status" class="form-control" required>
                                                                <option value="ativo" ' . ($user->status == 'ativo' ? 'selected' : '') . '>Ativo</option>
                                                                <option value="desativado" ' . ($user->status == 'desativado' ? 'selected' : '') . '>Desativado</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label" for="renewUserTrialEndsAt' . $user->id . '">Data de Término do Teste</label>
                                                            <input type="date" id="renewUserTrialEndsAt' . $user->id . '" name="trial_ends_at" class="form-control" value="' . ($user->trial_ends_at ?? \Carbon\Carbon::now()->format('Y-m-d')) . '" required />
                                                        </div>
                                                        <div class="col-12 text-center">
                                                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Salvar</button>
                                                            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
    
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'user_id' => $user->user_id ? ($user->parent ? $user->parent->name : 'N/A') : 'Administrador',
                            'whatsapp' => $user->whatsapp,
                            'role' => $user->role->name,
                            'status' => $user->status,
                            'plano' => optional($user->plano)->nome ?? 'Sem Plano',
                            'limite' => $user->limite,
                            'creditos' => $user->creditos,
                            'trial_ends_at' => \Carbon\Carbon::parse($user->trial_ends_at)->format('d/m/Y H:i'),
                            'created_at' => $user->created_at->format('d/m/Y H:i:s'),
                            'updated_at' => $user->updated_at->format('d/m/Y H:i:s'),
                            'actions' => $actions . $modalEdit . $modalRenew
                        ];
                    });
    
                // Fetch user preferences for visible columns
                $userId = $user->id;
                $preferences = DB::table('user_client_preferences')
                    ->where('user_id', $userId)
                    ->where('table_name', 'users')
                    ->value('visible_columns');
    
                $visibleColumns = json_decode($preferences, true) ?: [
                    'id',
                    'name',
                    'user_id',
                    'whatsapp',
                    'role',
                    'status',
                    'plano',
                    'limite',
                    'creditos',
                    'trial_ends_at',
                    'created_at',
                    'updated_at',
                    'actions'
                ];
    
                // Filter the columns based on user preferences
                $filteredUsers = $users->map(function ($user) use ($visibleColumns) {
                    return array_filter($user, function ($key) use ($visibleColumns) {
                        return in_array($key, $visibleColumns);
                    }, ARRAY_FILTER_USE_KEY);
                });
    
                // Adicionar dados adicionais que eram retornados no método index
                $planos_revenda = PlanoRenovacao::all();
                $current_plan_id = $user->plano_id;
    
                return response()->json([
                    'rows' => $filteredUsers,
                    'total' => $totalUsers,
                    'planos_revenda' => $planos_revenda,
                    'current_plan_id' => $current_plan_id
                ]);
            } else {
                // Usuário não está autenticado
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao acessar a listagem de usuários: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao acessar a listagem de usuários'], 500);
        }
    }

    public function create()
    {
        return view('content.apps.app-user-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'whatsapp' => 'required',
            'password' => 'required',
            'role_id' => 'required',
            'status' => 'required',
            'plano_id' => 'required',
            'limite' => 'required',
            'creditos' => 'required',
        ]);

        User::create($request->all());

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('content.apps.app-user-edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'whatsapp' => 'required',
            'role_id' => 'required',
            'status' => 'required',
            'plano_id' => 'required|exists:planos_renovacao,id',
            'limite' => 'required|integer',
            'creditos' => 'required|integer',
        ]);

        // Buscar o plano selecionado
        $plano = PlanoRenovacao::findOrFail($request->plano_id);

        // Atualizar os dados do usuário
        $user->name = $request->name;
        $user->whatsapp = $request->whatsapp;
        $user->role_id = $request->role_id;
        $user->status = $request->status;
        $user->plano_id = $request->plano_id;
        $user->limite = $plano->limite; // Atualizar o limite com base no plano selecionado
        $user->creditos = $request->creditos;
        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function renew(Request $request, User $user)
    {
        $request->validate([
            'status' => 'required|string',
            'trial_ends_at' => 'required|date',
        ]);

        $user->update($request->only('status', 'trial_ends_at'));

        return redirect()->route('users.index')->with('success', 'Usuário renovado com sucesso.');
    }

      public function destroyMultiple(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);
    
        try {
            User::whereIn('id', $request->user_ids)->delete();
            return response()->json(['error' => false, 'message' => 'Usuários excluídos com sucesso.']);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => 'Erro ao excluir usuários: ' . $e->getMessage()]);
        }
    }
}