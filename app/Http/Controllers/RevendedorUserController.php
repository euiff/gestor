<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\PlanoRenovacao;
use App\Models\Revenda;

class RevendedorUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!in_array($user->role_id, [1, 2])) {
                return redirect('/app/ecommerce/dashboard')->with('error', 'Você não tem permissão para acessar esta página.');
            }
            return $next($request);
        });
    }

    public function create(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $userId = $user->id;
            $userRole = $user->role->name;

            $planos_revenda = PlanoRenovacao::all();
            $current_plan_id = $user->plano_id;
            $revendas_creditos = Revenda::all();

            $query = User::query();

            if ($userRole !== 'admin') {
                $query->where('user_id', $userId);
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('whatsapp', 'like', '%' . $search . '%');
                });
            }

            $usuarios = $query->get();

            return view('revendedores.create', compact('user', 'planos_revenda', 'current_plan_id', 'revendas_creditos', 'usuarios'));
        }
    }    
       public function list(Request $request)
    {
        Log::info('Acessando a listagem de revendedores com paginação e busca.');
    
        try {
            if (Auth::check()) {
                $user = Auth::user();
                $userRole = $user->role->name;
    
                $search = $request->input('search');
                $sort = $request->input('sort', 'id');
                $order = $request->input('order', 'DESC');
    
                if ($userRole === 'admin') {
                    $filter = $request->input('filter', 'all');
    
                    if ($filter == 'mine') {
                        $revendedores = User::where('user_id', $user->id);
                    } else {
                        $revendedores = User::query();
                    }
                } else {
                    $revendedores = User::where('user_id', $user->id);
                }
    
                if ($search) {
                    $revendedores = $revendedores->where(function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%')
                              ->orWhere('whatsapp', 'like', '%' . $search . '%');
                    });
                }
    
                $totalRevendedores = $revendedores->count();
                $canEdit = true;
                $canDelete = true;
    
                $planos_revenda = PlanoRenovacao::all();
                $current_plan_id = $user->plano_id;
                $revendas_creditos = Revenda::all();
    
                $revendedores = $revendedores->orderBy($sort, $order)
                    ->paginate($request->input('limit', 10))
                    ->through(function ($revendedor) use ($canEdit, $canDelete, $planos_revenda) {
                        $actions = '<div class="d-grid gap-3">
                                        <div class="row g-3">
                                            <div class="col-6 mb-2">
                                                <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#editUserModal" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <form action="' . route('revendedores.destroy', $revendedor->id) . '" method="POST" style="display:inline;">
                                                    ' . csrf_field() . '
                                                    ' . method_field('DELETE') . '
                                                    <button type="submit" class="btn btn-sm btn-danger w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Deletar">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>';
    
                        $modal = '<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-simple modal-edit-user">
                                        <div class="modal-content p-3 p-md-5">
                                            <div class="modal-body">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                <div class="text-center mb-4">
                                                    <h3 class="mb-2">Editar Usuário</h3>
                                                    <p class="text-muted">Atualize os detalhes do usuário.</p>
                                                </div>
                                                <form id="editUserForm" class="row g-3" action="' . route('revendedores.update', $revendedor->id) . '" method="POST">
                                                    ' . csrf_field() . '
                                                    ' . method_field('PUT') . '
                                                    <div class="col-12">
                                                        <label class="form-label" for="edit_name">Nome</label>
                                                        <input type="text" class="form-control" id="edit_name" name="name" value="' . $revendedor->name . '" required>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label" for="edit_whatsapp">WhatsApp</label>
                                                        <input type="text" class="form-control" id="edit_whatsapp" name="whatsapp" value="' . $revendedor->whatsapp . '" required>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label" for="edit_password">Senha</label>
                                                        <input type="password" class="form-control" id="edit_password" name="password">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label" for="edit_plano_id">Plano</label>
                                                        <select id="edit_plano_id" name="plano_id" class="form-control" required>';
                        foreach ($planos_revenda as $plano) {
                            $modal .= '<option value="' . $plano->id . '" ' . ($revendedor->plano_id == $plano->id ? 'selected' : '') . '>
                                        ' . $plano->nome . ' - R$ ' . number_format((float) $plano->preco, 2, ',', '.') . '
                                    </option>';
                        }
                        $modal .= '</select>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label" id="trial_ends_at" for="edit_trial_ends_at">Duração do Período de Teste (em meses)</label>
                                                        <select id="trial_ends_at" name="trial_ends_at" class="form-control" required>';
                        for ($i = 1; $i <= 12; $i++) {
                            $modal .= '<option value="' . $i . '">' . $i . ' mês' . ($i > 1 ? 'es' : '') . '</option>';
                        }
                        $modal .= '</select>
                                                        <small id="edit_creditoInfo" class="form-text text-muted mt-2"></small>
                                                    </div>
                                                    <input type="hidden" id="edit_creditos_necessarios" name="creditos_necessarios" value="1">
                                                    <input type="hidden" id="edit_plano_limite" name="plano_limite" value="">
                                                    <div class="col-12 text-center">
                                                        <button type="submit" class="btn btn-primary me-sm-3 me-1">Salvar Alterações</button>
                                                        <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>';
    
                        $statusBadge = '<span class="badge bg-label-secondary me-1">Desconhecido</span>';
                        if ($revendedor->status == 'ativo') {
                            $statusBadge = '<span class="badge bg-label-primary me-1">Ativo</span>';
                        } elseif ($revendedor->status == 'desativado') {
                            $statusBadge = '<span class="badge bg-label-warning me-1">Inativo</span>';
                        }
    
                        $trialEndsAtBadge = '<span class="badge bg-label-secondary me-1">Sem Vencimento</span>';
                        if ($revendedor->trial_ends_at) {
                            $trialEndsAtBadge = '<span class="badge bg-label-primary me-1">Vencimento: ' . $revendedor->trial_ends_at->format('d/m/Y') . '</span>';
                        }
    
                        $limiteBadge = '<span class="badge bg-label-secondary me-1">Sem Limite</span>';
                        if ($revendedor->limite) {
                            $limiteBadge = '<span class="badge bg-label-primary me-1">Limite: ' . $revendedor->limite . '</span>';
                        }
    
                        return [
                            'id' => $revendedor->id,
                            'name' => $revendedor->name,
                            'whatsapp' => $revendedor->whatsapp,
                            'plano_id' => $revendedor->plano_id,
                            'trial_ends_at' => $trialEndsAtBadge,
                            'created_at' => $revendedor->created_at->format('d/m/Y H:i:s'),
                            'updated_at' => $revendedor->updated_at->format('d/m/Y H:i:s'),
                            'profile_photo_url' => $revendedor->profile_photo_url ? $revendedor->profile_photo_url : asset('assets/img/avatars/1.png'), // URL da foto de perfil
                            'status' => $statusBadge,
                            'limite' => $limiteBadge,
                            'actions' => $actions . $modal
                        ];
                    });
    
                $userId = $user->id;
                $preferences = DB::table('user_client_preferences')
                    ->where('user_id', $userId)
                    ->where('table_name', 'revendedores')
                    ->value('visible_columns');
    
                $visibleColumns = json_decode($preferences, true) ?: [
                    'id',
                    'name',
                    'whatsapp',
                    'plano_id',
                    'trial_ends_at',
                    'created_at',
                    'updated_at',
                    'profile_photo_url',
                    'status',
                    'limite',
                    'actions'
                ];
    
                $filteredRevendedores = $revendedores->map(function ($revendedor) use ($visibleColumns) {
                    return array_filter($revendedor, function ($key) use ($visibleColumns) {
                        return in_array($key, $visibleColumns);
                    }, ARRAY_FILTER_USE_KEY);
                });
    
                return response()->json([
                    'rows' => $filteredRevendedores,
                    'total' => $totalRevendedores,
                    'planos_revenda' => $planos_revenda,
                    'current_plan_id' => $current_plan_id,
                    'revendas_creditos' => $revendas_creditos,
                    'usuarios' => $revendedores
                ]);
            } else {
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao acessar a listagem de revendedores: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao acessar a listagem de revendedores'], 500);
        }
    }
    public function store(Request $request)
    {
        Log::info('Requisição recebida', ['request' => $request->all(), 'user' => Auth::user()]);

        $user = Auth::user();
        $userRole = $user->role->name;

        $creditosNecessarios = $request->trial_ends_at;

        if ($userRole !== 'admin' && $user->creditos < $creditosNecessarios) {
            Log::warning('Usuário sem créditos suficientes', ['user' => $user, 'creditos_necessarios' => $creditosNecessarios]);
            return redirect()->route('revendedores.create')->with('error', 'Você não tem créditos suficientes para criar um novo usuário.');
        }

        Log::info('Usuário autenticado', ['user' => $user]);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'whatsapp' => 'required|string|max:15|unique:users,whatsapp',
            'password' => 'required|string|min:8',
            'trial_ends_at' => 'required|integer|min:1|max:12',
            'plano_id' => 'required|exists:planos_renovacao,id',
        ], [
            'name.required' => 'O campo nome é obrigatório.',
            'name.unique' => 'Este nome já está em uso.',
            'whatsapp.required' => 'O campo WhatsApp é obrigatório.',
            'whatsapp.max' => 'O campo WhatsApp não pode ter mais que 15 caracteres.',
            'whatsapp.unique' => 'Este WhatsApp já está em uso.',
            'password.required' => 'O campo senha é obrigatório.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'plano_id.required' => 'O campo plano é obrigatório.',
            'plano_id.exists' => 'O plano selecionado é inválido.',
        ]);

        Log::info('Dados validados', ['validatedData' => $validatedData]);

        $trialEndsAt = Carbon::now()->addMonths($request->trial_ends_at);
        Log::info('Data de término do período de teste calculada', ['trial_ends_at' => $trialEndsAt]);

        $plano = PlanoRenovacao::find($request->plano_id);
        if (!$plano) {
            Log::error('Plano de renovação não encontrado', ['plano_id' => $request->plano_id]);
            return redirect()->route('revendedores.create')->with('error', 'Plano de renovação não encontrado.');
        }

        Log::info('Plano de renovação encontrado', ['plano' => $plano]);

        $newUser = User::create([
            'name' => $request->name,
            'whatsapp' => $request->whatsapp,
            'password' => Hash::make($request->password),
            'role_id' => 4,
            'trial_ends_at' => $trialEndsAt,
            'status' => 'ativo',
            'plano_id' => $request->plano_id,
            'limite' => $plano->limite,
            'creditos' => 0,
            'user_id' => $user->id,
            'profile_photo_url' => '/assets/img/avatars/14.png', // Valor padrão para profile_photo_url
        ]);
        Log::info('Novo usuário criado', ['newUser' => $newUser]);

        if ($userRole !== 'admin') {
            $user->creditos -= $creditosNecessarios;
            $user->save();
            Log::info('Créditos do usuário atualizados', ['user' => $user]);
        }

        return redirect()->route('revendedores.create')->with('success', 'Usuário criado com sucesso!');
    }

    public function update(Request $request, $id)
    {
        Log::info('Requisição recebida para edição', ['request' => $request->all(), 'user' => Auth::user()]);

        $user = Auth::user();
        $userRole = $user->role->name;

        $usuario = User::findOrFail($id);

        $creditosNecessarios = $request->trial_ends_at;

        if ($userRole !== 'admin' && $user->creditos < $creditosNecessarios) {
            Log::warning('Usuário sem créditos suficientes', ['user' => $user, 'creditos_necessarios' => $creditosNecessarios]);
            return redirect()->route('revendedores.create')->with('error', 'Você não tem créditos suficientes para editar este usuário.');
        }

        Log::info('Usuário autenticado', ['user' => $user]);

        Log::info('ID do usuário atual', ['usuario_id' => $usuario->id]);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:users,name,' . $usuario->id,
            'whatsapp' => 'required|string|max:15|unique:users,whatsapp,' . $usuario->id,
            'password' => 'nullable|string|min:8',
            'trial_ends_at' => 'required|integer|min:1|max:12',
            'plano_id' => 'required|exists:planos_renovacao,id',
        ], [
            'name.required' => 'O campo nome é obrigatório.',
            'name.unique' => 'Este nome já está em uso.',
            'whatsapp.required' => 'O campo WhatsApp é obrigatório.',
            'whatsapp.max' => 'O campo WhatsApp não pode ter mais que 15 caracteres.',
            'whatsapp.unique' => 'Este WhatsApp já está em uso.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'plano_id.required' => 'O campo plano é obrigatório.',
            'plano_id.exists' => 'O plano selecionado é inválido.',
        ]);

        Log::info('Dados validados', ['validatedData' => $validatedData]);

        $trialEndsAt = Carbon::now()->addMonths($request->trial_ends_at);
        Log::info('Data de término do período de teste calculada', ['trial_ends_at' => $trialEndsAt]);

        $plano = PlanoRenovacao::find($request->plano_id);
        if (!$plano) {
            Log::error('Plano de renovação não encontrado', ['plano_id' => $request->plano_id]);
            return redirect()->route('revendedores.create')->with('error', 'Plano de renovação não encontrado.');
        }

        Log::info('Plano de renovação encontrado', ['plano' => $plano]);

        $usuario->name = $request->name;
        $usuario->whatsapp = $request->whatsapp;
        if ($request->filled('password')) {
            $usuario->password = Hash::make($request->password);
        }
        $usuario->trial_ends_at = $trialEndsAt;
        $usuario->plano_id = $request->plano_id;
        $usuario->limite = $plano->limite;
        $usuario->save();

        Log::info('Usuário atualizado', ['usuario' => $usuario]);

        if ($userRole !== 'admin') {
            $user->creditos -= $creditosNecessarios;
            $user->save();
            Log::info('Créditos do usuário atualizados', ['user' => $user]);
        }

        return redirect()->route('revendedores.create')->with('success', 'Usuário atualizado com sucesso!');
    }

    public function ativar($id)
    {
        $cliente = User::findOrFail($id);
        $cliente->status = 'ativo';
        $cliente->save();

        Log::info('Cliente ativado', ['cliente_id' => $id]);
        return redirect()->route('revendedores.create')->with('success', 'Cliente ativado com sucesso!');
    }

    public function desativar($id)
    {
        $cliente = User::findOrFail($id);
        $cliente->status = 'desativado';
        $cliente->save();

        Log::info('Cliente desativado', ['cliente_id' => $id]);

        return redirect()->route('revendedores.create')->with('success', 'Cliente desativado com sucesso!');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $userId = $user->id;
        $userRole = $user->role->name;

        $usuario = User::findOrFail($id);

        $usuario->delete();

        return redirect()->route('revendedores.create')->with('success', 'Usuário deletado com sucesso!');
    }

    // destroyMultiple
      public function destroyMultiple(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;
        $userRole = $user->role->name;
    
        // Verificar se o usuário tem permissão para deletar usuários
        if ($userRole !== 'admin') {
            return response()->json(['error' => true, 'message' => 'Você não tem permissão para deletar usuários.'], 403);
        }
    
        $ids = $request->input('ids');
    
        if (empty($ids)) {
            return response()->json(['error' => true, 'message' => 'Nenhum usuário selecionado para deletar.'], 400);
        }
    
        $usuarios = User::whereIn('id', $ids)->get();
    
        foreach ($usuarios as $usuario) {
            $usuario->delete();
        }
    
        return response()->json(['error' => false, 'message' => 'Usuários deletados com sucesso!'], 200);
    }
}
