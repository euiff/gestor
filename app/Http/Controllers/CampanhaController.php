<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campanha;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;
use App\Models\PlanoRenovacao;
use App\Models\Plano;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\Servidor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Log;

class CampanhaController extends Controller
{

    public function index()
{
    $user = Auth::user();
    $domain = request()->getHost();

    // Obter preferências de colunas visíveis
    $preferences = DB::table('user_client_preferences')
        ->where('user_id', $user->id)
        ->where('table_name', 'campanhas')
        ->first();

    // Definir colunas visíveis padrão
    $defaultColumns = ['id', 'nome', 'horario', 'data', 'contatos_count', 'status', 'actions'];
    $visibleColumns = $defaultColumns;

    if ($preferences && $preferences->visible_columns) {
        $decoded = json_decode($preferences->visible_columns, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $visibleColumns = $decoded;
        }
    }

    // Consultas básicas que serão usadas tanto para admin quanto para usuários normais
    $baseQuery = [
        'clientes' => Cliente::query(),
        'servidores' => Servidor::query()->withCount('clientes'),
        'campanhas' => Campanha::query()->with('user')
    ];

    if ($user->role->name !== 'admin') {
        foreach ($baseQuery as &$query) {
            $query->where('user_id', $user->id);
        }
    }

    // Obter os dados
    $clientes = $baseQuery['clientes']->get();
    $servidores = $baseQuery['servidores']->get();
    
    // Não precisamos mais da paginação aqui pois a tabela usa AJAX
    $campanhas = $baseQuery['campanhas']->take(5)->get(); // Apenas alguns para exibição inicial

    // Filtrar clientes por status
    $hoje = now()->format('Y-m-d');
    $clientesVencidos = $clientes->where('vencimento', '<', $hoje);
    $clientesVencemHoje = $clientes->where('vencimento', $hoje);
    $clientesAtivos = $clientes->where('vencimento', '>', $hoje);

    $planos = Plano::all();
    $planos_revenda = PlanoRenovacao::all();
    $current_plan_id = $user->plano_id;

    return view('campanhas.index', [
        'campanhas' => $campanhas,
        'planos' => $planos,
        'planos_revenda' => $planos_revenda,
        'current_plan_id' => $current_plan_id,
        'clientes' => $clientes,
        'clientesVencidos' => $clientesVencidos,
        'clientesVencemHoje' => $clientesVencemHoje,
        'clientesAtivos' => $clientesAtivos,
        'domain' => $domain,
        'servidores' => $servidores,
        'visibleColumns' => $visibleColumns // Passa as colunas visíveis para a view
    ]);
}

    public function create()
    {
        $user = Auth::user();
        $clientes = $user->role->name === 'admin' 
            ? Cliente::all() 
            : Cliente::where('user_id', $user->id)->get();
        
        $servidores = $user->role->name === 'admin' 
            ? Servidor::all() 
            : Servidor::where('user_id', $user->id)->get();
        
        return view('campanhas.create', compact('clientes', 'servidores'));
    }

     public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
            'horario' => 'required|date_format:H:i',
            'data' => 'nullable|date',
            'contatos' => 'nullable|array',
            'servidores' => 'nullable|array',
            'origem_contatos' => 'required|string|in:clientes,servidores',
            'ignorar_contatos' => 'required|boolean',
            'mensagem' => 'required|string',
            'arquivo' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,mp4,avi,mov,wmv|max:20480',
            'enviar_diariamente' => 'required|boolean',
        ]);

        $user = Auth::user();

        // Processar contatos
        $contatos = [];
        if ($validatedData['origem_contatos'] === 'servidores' && !empty($validatedData['servidores'])) {
            foreach ($validatedData['servidores'] as $servidorId) {
                $servidor = Servidor::with('clientes')->find($servidorId);
                if ($servidor) {
                    foreach ($servidor->clientes as $cliente) {
                        $contatos[] = $cliente->id;
                    }
                }
            }
        } else {
            $contatos = $validatedData['contatos'] ?? [];
        }

        // Processar arquivo
        $arquivoPath = null;
        if ($request->hasFile('arquivo')) {
            $directory = public_path('assets/campanhas_arquivos');
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            $fileName = time().'_'.Str::slug($request->file('arquivo')->getClientOriginalName());
            $path = $request->file('arquivo')->move($directory, $fileName);
            
            if ($path) {
                $arquivoPath = '/assets/campanhas_arquivos/'.$fileName;
            }
        }

        // Criar campanha
        $campanha = new Campanha();
        $campanha->user_id = $user->id;
        $campanha->nome = $validatedData['nome'];
        $campanha->horario = $validatedData['horario'];
        $campanha->data = $validatedData['data'];
        $campanha->origem_contatos = $validatedData['origem_contatos'];
        $campanha->ignorar_contatos = $validatedData['ignorar_contatos'];
        $campanha->mensagem = $validatedData['mensagem'];
        $campanha->enviar_diariamente = $validatedData['enviar_diariamente'];
        $campanha->contatos = $contatos;
        $campanha->arquivo = $arquivoPath;
        
        if (!$campanha->save()) {
            throw new \Exception('Falha ao salvar campanha no banco de dados');
        }

        DB::commit();
        
        return redirect()->route('campanhas.index')
        ->with('success', 'Campanha criada com sucesso!');


    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Erro de validação',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erro ao criar campanha: '.$e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erro ao criar campanha: '.$e->getMessage()
        ], 500);
    }
}
    

    public function show($id)
    {
        $campanha = Campanha::findOrFail($id);
        return view('campanhas.show', compact('campanha'));
    }
    
    public function listar(Request $request)
    {
        Log::info('Acessando a listagem de campanhas com paginação e busca.');
    
        try {
            if (Auth::check()) {
                $user = Auth::user();
    
                $search = $request->input('search');
                $sort = $request->input('sort', 'id');
                $order = $request->input('order', 'DESC');
                $limit = $request->input('limit', 10);
                $offset = $request->input('offset', 0);
    
                // Consulta base
                $query = Campanha::where('user_id', $user->id)
                    ->withCount('contatos')
                    ->with('user');
    
                if ($search) {
                    $query->where('nome', 'like', '%' . $search . '%');
                }
    
                $total = $query->count();
                $campanhas = $query->orderBy($sort, $order)
                    ->skip($offset)
                    ->take($limit)
                    ->get();
    
                // Preferências de colunas
                $preferences = DB::table('user_client_preferences')
                    ->where('user_id', $user->id)
                    ->where('table_name', 'campanhas')
                    ->first();
    
                $defaultColumns = ['id', 'nome', 'horario', 'data', 'contatos_count', 'status', 'actions'];
                $visibleColumns = $defaultColumns;
    
                if ($preferences && $preferences->visible_columns) {
                    $decoded = json_decode($preferences->visible_columns, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $visibleColumns = $decoded;
                    }
                }
    
                $rows = $campanhas->map(function ($campanha) use ($visibleColumns) {
                    // ... (mantenha o mesmo código de formatação dos dados)
                    return $formattedData;
                });
    
                return response()->json([
                    'total' => $total,
                    'rows' => $rows,
                    'columns' => $visibleColumns
                ]);
            }
            return response()->json(['error' => 'Unauthorized'], 401);
        } catch (\Exception $e) {
            Log::error('Erro ao listar campanhas: ' . $e->getMessage());
            return response()->json(['error' => 'Erro interno'], 500);
        }
    }

    public function edit($id)
    {
        $campanha = Campanha::findOrFail($id);
        $user = Auth::user();
        $clientes = Cliente::where('user_id', $user->id)->get();
        return view('campanhas.edit', compact('campanha', 'clientes'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'horario' => 'required|date_format:H:i',
            'data' => 'nullable|date',
            'contatos' => 'nullable|array',
            'servidores' => 'nullable|array',
            'origem_contatos' => 'required|string',
            'ignorar_contatos' => 'required|boolean',
            'mensagem' => 'required|string',
            'arquivo' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,mp4,avi,mov,wmv|max:20480', // 20MB = 20480KB
        ]);
    
        $campanha = Campanha::findOrFail($id);
        $campanha->nome = $request->nome;
        $campanha->horario = $request->horario;
        $campanha->data = $request->data;
        $campanha->origem_contatos = $request->origem_contatos;
        $campanha->ignorar_contatos = $request->ignorar_contatos;
        $campanha->mensagem = $request->mensagem;
    
        // Processar contatos e servidores
        if ($request->origem_contatos === 'servidores' && $request->has('servidores')) {
            $contatos = [];
            foreach ($request->servidores as $servidorId) {
                $servidor = Servidor::with('clientes')->find($servidorId);
                if ($servidor) {
                    foreach ($servidor->clientes as $cliente) {
                        $contatos[] = $cliente->id;
                    }
                }
            }
            $campanha->contatos = $contatos;
        } else {
            $campanha->contatos = $request->contatos;
        }
    
        if ($request->hasFile('arquivo')) {
            // Remove o arquivo antigo, se existir
            if ($campanha->arquivo) {
                Storage::disk('public')->delete($campanha->arquivo);
            }
    
            // Define permissões 777 na pasta
            $directory = public_path('assets/campanhas_arquivos');
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
                \Log::info('Diretório criado: ' . $directory);
            }
            chmod($directory, 0777);
            \Log::info('Permissões definidas para o diretório: ' . $directory);
    
            // Store new file
            $fileName = $request->file('arquivo')->getClientOriginalName();
            $path = $request->file('arquivo')->move($directory, $fileName);
            if ($path) {
                $campanha->arquivo = '/assets/campanhas_arquivos/' . $fileName; // Salva o caminho relativo no banco de dados
                \Log::info('Novo arquivo armazenado em: ' . $campanha->arquivo);
            } else {
                \Log::error('Falha ao armazenar o novo arquivo.');
            }
        }
    
        $campanha->save();
    
        return redirect()->route('campanhas.index')->with('success', 'Campanha atualizada com sucesso!');
    }
    
public function list(Request $request)
{
    Log::info('Acessando a listagem de campanhas com paginação e busca.');

    try {
        if (Auth::check()) {
            $user = Auth::user();

            $search = $request->input('search');
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'DESC');
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            // Mostrar apenas as campanhas do usuário logado
            $query = Campanha::where('user_id', $user->id)
                ->withCount('contatos')
                ->with('user');

            if ($search) {
                $query->where('nome', 'like', '%' . $search . '%');
            }

            $total = $query->count();

            $campanhas = $query->orderBy($sort, $order)
                ->skip($offset)
                ->take($limit)
                ->get();

            // Busca as preferências do usuário
            $preferences = DB::table('user_client_preferences')
                ->where('user_id', $user->id)
                ->where('table_name', 'campanhas')
                ->first();

            // Define as colunas visíveis padrão
            $defaultColumns = [
                'id',
                'nome',
                'horario',
                'data',
                'contatos_count',
                'status',
                'actions'
            ];

            // Processa as colunas visíveis
            $visibleColumns = $defaultColumns;
            if ($preferences && $preferences->visible_columns) {
                $decoded = json_decode($preferences->visible_columns, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $visibleColumns = $decoded;
                }
            }

            $rows = $campanhas->map(function ($campanha) use ($visibleColumns) {
                // Renderiza os botões de ação
                $actions = '<div class="d-grid gap-3">
                                <div class="row g-3">
                                    <div class="col-4 mb-2">
                                        <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#editCampanha' . $campanha->id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                    <div class="col-4 mb-2">
                                        <form action="' . route('campanhas.duplicate', $campanha->id) . '" method="POST" style="display:inline;">
                                            ' . csrf_field() . '
                                            <button type="submit" class="btn btn-sm btn-info w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Duplicar">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-4 mb-2">
                                        <form action="' . route('campanhas.destroy', $campanha->id) . '" method="POST" style="display:inline;">
                                            ' . csrf_field() . '
                                            ' . method_field('DELETE') . '
                                            <button type="submit" class="btn btn-sm btn-danger w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Deletar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>';

                // Modal de edição da campanha
                $modal = '<div class="modal fade" id="editCampanha' . $campanha->id . '" tabindex="-1" aria-hidden="true">
                              <div class="modal-dialog modal-lg modal-simple modal-edit-campanha">
                                  <div class="modal-content p-3 p-md-5">
                                      <div class="modal-body">
                                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                          <div class="text-center mb-4">
                                              <h3 class="mb-2">Editar Campanha</h3>
                                              <p class="text-muted">Atualize os detalhes da campanha.</p>
                                          </div>
                                          <form id="editCampanhaForm' . $campanha->id . '" class="row g-3" action="' . route('campanhas.update', $campanha->id) . '" method="POST">
                                              ' . csrf_field() . '
                                              ' . method_field('PUT') . '
                                              <div class="col-12">
                                                  <label class="form-label" for="editCampanhaNome' . $campanha->id . '">Nome</label>
                                                  <input type="text" id="editCampanhaNome' . $campanha->id . '" name="nome" class="form-control" value="' . $campanha->nome . '" required />
                                              </div>
                                              <div class="col-12">
                                                  <label class="form-label" for="editCampanhaHorario' . $campanha->id . '">Horário</label>
                                                  <input type="text" id="editCampanhaHorario' . $campanha->id . '" name="horario" class="form-control" value="' . $campanha->horario . '" required />
                                              </div>
                                              <div class="col-12">
                                                  <label class="form-label" for="editCampanhaData' . $campanha->id . '">Data</label>
                                                  <input type="date" id="editCampanhaData' . $campanha->id . '" name="data" class="form-control" value="' . $campanha->data . '" required />
                                              </div>
                                              <div class="col-12 text-center">
                                                  <button type="submit" class="btn btn-primary me-sm-3 me-1">Atualizar</button>
                                                  <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                                              </div>
                                          </form>
                                      </div>
                                  </div>
                              </div>
                          </div>';

                // Determina o status da campanha
                $status = '';
                if ($campanha->enviar_diariamente) {
                    $status = '<span class="badge bg-info">Recorrente</span>';
                } elseif (!$campanha->data || !$campanha->horario) {
                    $status = '<span class="badge bg-warning">Pendente</span>';
                } else {
                    try {
                        $now = new DateTime();
                        $campanhaDate = new DateTime($campanha->data . ' ' . $campanha->horario);
                        
                        if ($campanhaDate < $now) {
                            $status = '<span class="badge bg-secondary">Enviada</span>';
                        } else {
                            $status = '<span class="badge bg-primary">Agendada</span>';
                        }
                    } catch (Exception $e) {
                        $status = '<span class="badge bg-warning">Pendente</span>';
                    }
                }

                $data = [
                    'id' => $campanha->id,
                    'nome' => $campanha->nome,
                    'horario' => $campanha->horario,
                    'data' => $campanha->data ? date('d/m/Y', strtotime($campanha->data)) : 'N/A',
                    'contatos_count' => $campanha->contatos_count,
                    'status' => $status,
                    'created_at' => $campanha->created_at->format('d/m/Y H:i:s'),
                    'updated_at' => $campanha->updated_at->format('d/m/Y H:i:s'),
                    'user_name' => $campanha->user ? $campanha->user->name : 'N/A',
                    'actions' => $actions . $modal,
                    'enviar_diariamente' => $campanha->enviar_diariamente
                ];

                // Filtra apenas as colunas visíveis
                return array_intersect_key($data, array_flip($visibleColumns));
            });

            return response()->json([
                'total' => $total,
                'rows' => $rows,
                'columns' => $visibleColumns
            ]);
        } else {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
    } catch (\Exception $e) {
        Log::error('Erro ao acessar a listagem de campanhas: ' . $e->getMessage());
        return response()->json(['error' => 'Erro ao acessar a listagem de campanhas'], 500);
    }
}

    public function destroy($id)
    {
        $campanha = Campanha::findOrFail($id);
        if ($campanha->arquivo) {
            Storage::disk('public')->delete($campanha->arquivo);
        }
        $campanha->delete();

        return redirect()->route('campanhas.index')->with('success', 'Campanha excluída com sucesso!');
    }
}
