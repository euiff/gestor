<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Servidor;
use Illuminate\Support\Facades\Auth;
use App\Models\PlanoRenovacao;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ServidorController extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de autenticação
        $this->middleware('auth');
    }

   
    public function index()
    {
        Log::info('Acessando a página de listagem de servidores.');
    
        $user = Auth::user();
        $planos_revenda = PlanoRenovacao::all();
        $current_plan_id = $user->plano_id;
    
        // Adicione a linha abaixo para obter os servidores
        $servidores = Servidor::all();
    
        return view('servidores.index', compact('planos_revenda', 'current_plan_id', 'servidores'));
    }

public function list(Request $request)
{
    Log::info('Acessando a listagem de servidores com paginação e busca.');

    if (Auth::check()) {
        $user = Auth::user();

        $search = $request->input('search');
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $status = $request->input('status');

        // Mostrar apenas os servidores do usuário logado
        $servidores = Servidor::where('user_id', $user->id)->withCount('clientes');

        if ($search) {
            $servidores = $servidores->where('nome', 'like', '%' . $search . '%');
        }

        if ($status !== null) {
            $servidores = $servidores->where('status', $status);
        }

        $totalServidores = $servidores->count();
        $canEdit = true;
        $canDelete = true;

        $servidores = $servidores->orderBy($sort, $order)
            ->paginate($request->input('limit', 10))
            ->through(function ($servidor) use ($canEdit, $canDelete) {
                $actions = '<div class="d-grid gap-3">
                                <div class="row g-3">
                                    <div class="col-6 mb-2">
                                        <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#editServidor' . $servidor->id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <button class="btn btn-sm btn-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteServidor' . $servidor->id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Deletar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>';

                // Modal para perguntar se deseja excluir
                $modal = '<div class="modal fade" id="deleteServidor' . $servidor->id . '" tabindex="-1" aria-labelledby="deleteServidor' . $servidor->id . 'Label" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteServidor' . $servidor->id . 'Label">Excluir Servidor</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Tem certeza que deseja excluir o servidor <strong>' . $servidor->nome . '</strong>?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <form action="' . route('servidores.destroy', $servidor->id) . '" method="POST" style="display:inline;">
                                            ' . csrf_field() . '
                                            ' . method_field('DELETE') . '
                                            <button type="submit" class="btn btn-danger">Excluir</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';

                return [
                    'id' => $servidor->id,
                    'nome' => $servidor->nome,
                    'clientes_count' => $servidor->clientes_count,
                    'created_at' => $servidor->created_at->format('d/m/Y H:i:s'),
                    'updated_at' => $servidor->updated_at->format('d/m/Y H:i:s'),
                    'actions' => $actions . $modal
                ];
            });

        $userId = getAuthenticatedUser(true);
        $preferences = DB::table('user_client_preferences')
            ->where('user_id', $userId)
            ->where('table_name', 'servidores')
            ->value('visible_columns');

        $visibleColumns = json_decode($preferences, true) ?: [
            'id',
            'nome',
            'clientes_count',
            'created_at',
            'updated_at',
            'actions'
        ];

        $filteredServidores = $servidores->map(function ($servidor) use ($visibleColumns) {
            return array_filter($servidor, function ($key) use ($visibleColumns) {
                return in_array($key, $visibleColumns);
            }, ARRAY_FILTER_USE_KEY);
        });

        return response()->json([
            'rows' => $filteredServidores,
            'total' => $totalServidores,
        ]);
    } else {
        return response()->json(['error' => 'Usuário não autenticado'], 401);
    }
}

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string',
        ]);

        $user = Auth::user();

        Servidor::create([
            'user_id' => $user->id,
            'nome' => $request->nome,
        ]);

        return redirect()->route('servidores.index')->with('success', 'Servidor criado com sucesso.');
    }

    public function show($id)
    {
        Log::info('Acessando a página de perfil do servidor.', ['servidor_id' => $id]);
        $servidor = Servidor::findOrFail($id);
        return view('servidores.servidor_profile', ['servidor' => $servidor]);
    }

    public function edit($id)
    {
        Log::info('Acessando a página de edição do servidor.', ['servidor_id' => $id]);
        $servidor = Servidor::findOrFail($id);
        return view('servidores.update_servidor', ['servidor' => $servidor]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nome' => 'required|string',
        ]);

        $servidor = Servidor::findOrFail($id);
        $servidor->update($request->all());

        return redirect()->route('servidores.index')->with('success', 'Servidor atualizado com sucesso.');
    }

    public function destroy($id)
    {
        $servidor = Servidor::findOrFail($id);
        $servidor->delete();
    
        return redirect()->route('servidores.index')->with('success', 'Servidor excluído com sucesso.');
    }

    public function deletarMultiplos(Request $request)
    {
        $ids = $request->input('ids');
        if (is_array($ids)) {
            Servidor::whereIn('id', $ids)->delete();
            return response()->json(['error' => false, 'message' => 'Servidores excluídos com sucesso.']);
        } else {
            return response()->json(['error' => true, 'message' => 'Nenhum servidor selecionado.']);
        }
    }
}