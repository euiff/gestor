<?php

namespace App\Http\Controllers;

use App\Models\PlanoRenovacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanoRenovacaoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (Auth::user()->role_id != 1) {
                return redirect('/app/ecommerce/dashboard')->with('error', 'Você não tem permissão para acessar esta página.');
            }
            return $next($request);
        });
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
               $planos_revenda = PlanoRenovacao::all();
            } else {
                // Se não for administrador, mostrar apenas os planos do usuário
               $planos_revenda = PlanoRenovacao::where('user_id', $userId)->get();
            }

            $current_plan_id = $user->plano_id;
            return view('planos.renovacao', compact('planos_revenda', 'current_plan_id'));
        }

        // Redirecionar para a página de login se o usuário não estiver autenticado
        return redirect()->route('login')->with('error', 'Você precisa estar logado para acessar esta página.');
    }

    public function create()
    {
        return view('planos.renovacao_create');
    }
public function store(Request $request)
{
    $request->validate([
        'nome' => 'required',
        'descricao' => 'nullable',
        'preco' => 'required|numeric',
        'detalhes' => 'nullable',
        'botao' => 'nullable',
        'limite' => 'required|numeric',
        // 'duracao' => 'required|integer|in:1,3,6,12',
    ]);

    PlanoRenovacao::create($request->only(['nome', 'descricao', 'preco', 'detalhes', 'botao', 'limite', 'duracao']));
    return redirect()->route('planos-renovacao.index')->with('success', 'Plano criado com sucesso.');
}

public function edit(PlanoRenovacao $planoRenovacao)
{
    return view('planos.renovacao_edit', compact('planoRenovacao'));
}

public function update(Request $request, $id)
{
    $planoRenovacao = PlanoRenovacao::find($id);

    if (!$planoRenovacao) {
        return redirect()->route('planos-renovacao.index')->with('error', 'Plano não encontrado.');
    }

    $request->validate([
        'nome' => 'required|string|max:255',
        'descricao' => 'nullable|string',
        'preco' => 'required|numeric',
        'detalhes' => 'nullable|string',
        'botao' => 'nullable',
        'limite' => 'required|numeric',
        // 'duracao' => 'required|integer|in:1,3,6,12',
    ]);

    $planoRenovacao->update($request->only(['nome', 'descricao', 'preco', 'detalhes', 'botao', 'limite', 'duracao']));
    return redirect()->route('planos-renovacao.index')->with('success', 'Plano atualizado com sucesso.');
}
    public function destroy($id)
    {
        $planoRenovacao = PlanoRenovacao::find($id);

        if (!$planoRenovacao) {
            return redirect()->route('planos-renovacao.index')->with('error', 'Plano não encontrado.');
        }

        Log::info('PlanoRenovacaoController@destroy', ['planoRenovacao' => $planoRenovacao->id]);
        $planoRenovacao->delete();
        return redirect()->route('planos-renovacao.index')->with('success', 'Plano excluído com sucesso.');
    }

public function list(Request $request)
{
    Log::info('Acessando a listagem de planos de renovação com paginação e busca.', ['request' => $request->all()]);

    try {
        if (Auth::check()) {
            $user = Auth::user();
            $userId = $user->id;
            $userRole = $user->role->name;

            // Verificar se o usuário é administrador
            if ($userRole === 'admin') {
                $planos = PlanoRenovacao::query();
            } else {
                $planos = PlanoRenovacao::where('user_id', $userId);
            }

            $search = $request->input('search');
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'DESC');

            Log::info('Parâmetros de busca', ['search' => $search, 'sort' => $sort, 'order' => $order]);

            if ($search) {
                $planos = $planos->where('nome', 'like', '%' . $search . '%');
            }

            $totalPlanos = $planos->count();

            // Fetch user preferences for visible columns
            $current_plan_id = $user->plano_id;
            Log::info('Fetching user preferences', ['user_id' => $userId, 'table' => 'planos_renovacao', 'column' => 'visible_columns']);
            $preferences = DB::table('user_client_preferences')
                ->where('user_id', $userId)
                ->where('table_name', 'planos_renovacao')
                ->value('visible_columns');

            Log::info('User preferences result', ['result' => $preferences]);

            $visibleColumns = json_decode($preferences, true) ?: [
                'id',
                'nome',
                'descricao',
                'preco',
                'detalhes',
                'botao',
                'limite',
                'actions'
            ];

            Log::info('Colunas visíveis', ['visibleColumns' => $visibleColumns]);

            $planos = $planos->orderBy($sort, $order)
                ->paginate($request->input('limit', 10))
                ->through(function ($plano) use ($visibleColumns) {
                    $actions = '<div class="d-grid gap-3">
                                    <div class="row g-3">
                                        <div class="col-6 mb-2">
                                            <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#editPlanoRenovacaoModal' . $plano->id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <form action="' . route('planos-renovacao.destroy', $plano->id) . '" method="POST" style="display:inline;">
                                                ' . csrf_field() . '
                                                ' . method_field('DELETE') . '
                                                <button type="submit" class="btn btn-sm btn-danger w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Deletar">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>';

                    $modal = '<div class="modal fade" id="editPlanoRenovacaoModal' . $plano->id . '" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-simple modal-edit-plano-renovacao">
                                    <div class="modal-content p-3 p-md-5">
                                        <div class="modal-body">
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            <div class="text-center mb-4">
                                                <h3 class="mb-2">Editar Plano de Renovação</h3>
                                                <p class="text-muted">Atualize os detalhes do plano de renovação.</p>
                                            </div>
                                            <form id="editPlanoRenovacaoForm' . $plano->id . '" class="row g-3" action="' . route('planos-renovacao.update', $plano->id) . '" method="POST">
                                                ' . csrf_field() . '
                                                ' . method_field('PUT') . '
                                                <div class="col-12">
                                                    <label class="form-label" for="editPlanoRenovacaoNome' . $plano->id . '">Nome</label>
                                                    <input type="text" id="editPlanoRenovacaoNome' . $plano->id . '" name="nome" class="form-control" value="' . $plano->nome . '" required 
                                                        ' . ($plano->nome === 'Básico' ? 'readonly' : '') . ' />
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label" for="editPlanoRenovacaoDescricao' . $plano->id . '">Descrição</label>
                                                    <textarea id="editPlanoRenovacaoDescricao' . $plano->id . '" name="descricao" class="form-control">' . $plano->descricao . '</textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label" for="editPlanoRenovacaoPreco' . $plano->id . '">Preço</label>
                                                    <input type="number" step="0.01" id="editPlanoRenovacaoPreco' . $plano->id . '" name="preco" class="form-control" value="' . $plano->preco . '" required />
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label" for="editPlanoRenovacaoDetalhes' . $plano->id . '">Detalhes</label>
                                                    <textarea id="editPlanoRenovacaoDetalhes' . $plano->id . '" name="detalhes" class="form-control">' . $plano->detalhes . '</textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label" for="editPlanoRenovacaoBotao' . $plano->id . '">Botão</label>
                                                    <input type="text" id="editPlanoRenovacaoBotao' . $plano->id . '" name="botao" class="form-control" value="' . $plano->botao . '" />
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label" for="editPlanoRenovacaoLimite' . $plano->id . '">Limite</label>
                                                    <input type="number" id="editPlanoRenovacaoLimite' . $plano->id . '" name="limite" class="form-control" value="' . $plano->limite . '" required />
                                                </div>
                                                <div class="col-12 text-center">
                                                    <button type="submit" class="btn btn-primary me-sm-3 me-1">Salvar</button>
                                                    <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>';

                    $filteredPlano = array_filter($plano->toArray(), function ($key) use ($visibleColumns) {
                        return in_array($key, $visibleColumns);
                    }, ARRAY_FILTER_USE_KEY);

                    $filteredPlano['actions'] = $actions . $modal;

                    return $filteredPlano;
                });

            Log::info('Planos filtrados', ['planos' => $planos]);

            return response()->json([
                'rows' => $planos->items(),
                'total' => $totalPlanos,
                'current_plan_id' => $current_plan_id
            ]);
        } else {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
    } catch (\Exception $e) {
        Log::error('Erro ao acessar a listagem de planos de renovação: ' . $e->getMessage());
        return response()->json(['error' => 'Erro ao acessar a listagem de planos de renovação'], 500);
    }
}

       public function destroyMultiple(Request $request)
    {
        $ids = $request->input('ids');
    
        if (empty($ids)) {
            return response()->json(['error' => true, 'message' => 'Nenhum plano selecionado para exclusão.'], 400);
        }
    
        PlanoRenovacao::whereIn('id', $ids)->delete();
        return response()->json(['error' => false, 'message' => 'Planos excluídos com sucesso.']);
    }
}