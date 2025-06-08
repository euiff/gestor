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

class CampanhasController extends Controller
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
// Definir colunas visíveis padrão
$defaultColumns = ['id', 'nome', 'horario', 'data', 'ultima_execucao', 'contatos_count', 'status', 'actions'];
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
                'data_hidden' => 'nullable|date',
                'contatos' => 'nullable|array',
                'origem_contatos' => 'required|string|in:manual,vencidos,ativos',
                'ignorar_contatos' => 'sometimes|boolean',
                'mensagem' => 'required|string',
                'arquivo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', // Apenas imagens, max 5MB
                'enviar_diariamente' => 'sometimes|boolean', // Alterado para sometimes (opcional)
            ]);
    
            $user = Auth::user();
            $hoje = now()->format('Y-m-d');
            
            // Definir valor padrão para enviar_diariamente se não estiver presente
            $enviarDiariamente = $validatedData['enviar_diariamente'] ?? false;
            
            // Lógica para tratamento da data
            $data = null;
            if (!$enviarDiariamente) {
                $data = $validatedData['data'] ?? $validatedData['data_hidden'] ?? $hoje;
            }
            
            $validatedData['ignorar_contatos'] = $validatedData['ignorar_contatos'] ?? false;
    
            // Processar contatos
            $contatos = [];
            switch ($validatedData['origem_contatos']) {
                case 'manual':
                    $contatos = $validatedData['contatos'] ?? [];
                    break;
                case 'vencidos':
                    $contatos = $user->clientes()
                        ->where('vencimento', '<', $hoje)
                        ->pluck('id')
                        ->toArray();
                    break;
                case 'ativos':
                    $contatos = $user->clientes()
                        ->where('vencimento', '>=', $hoje)
                        ->pluck('id')
                        ->toArray();
                    break;
            }
    
            // Processar imagem
            $imagemPath = null;
            if ($request->hasFile('arquivo')) {
                $directory = public_path('assets/campanhas_imagens');
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
    
                $fileName = time().'_'.Str::slug($request->file('arquivo')->getClientOriginalName());
                $path = $request->file('arquivo')->move($directory, $fileName);
                
                if ($path) {
                    $imagemPath = '/assets/campanhas_imagens/'.$fileName;
                }
            }
    
            // Criar campanha
            $campanha = new Campanha();
            $campanha->user_id = $user->id;
            $campanha->nome = $validatedData['nome'];
            $campanha->horario = $validatedData['horario'];
            $campanha->data = $data;
            $campanha->origem_contatos = $validatedData['origem_contatos'];
            $campanha->ignorar_contatos = $validatedData['ignorar_contatos'];
            $campanha->mensagem = $validatedData['mensagem'];
            $campanha->enviar_diariamente = $enviarDiariamente;
            $campanha->contatos = $contatos;
            $campanha->arquivo = $imagemPath;
            
            if (!$campanha->save()) {
                throw new \Exception('Falha ao salvar campanha no banco de dados');
            }
    
            DB::commit();
            
            return redirect()->route('campanhas.index')
                ->with('success', 'Campanha criada com sucesso!');
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar campanha: '.$e->getMessage());
            return back()->with('error', 'Erro ao criar campanha: '.$e->getMessage());
        }
    }
    

    public function show($id)
    {
        $campanha = Campanha::findOrFail($id);
        return view('campanhas.show', compact('campanha'));
    }
    
    public function exibir(Request $request)
    {
        try {
            $user = Auth::user();
            
            $query = Campanha::with('user')
                ->where('user_id', $user->id);
    
            // Filtro de busca
            if ($search = $request->input('search')) {
                $query->where('nome', 'like', '%'.$search.'%');
            }
    
            // Paginação e ordenação
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'desc');
    
            $total = $query->count();
            
            $campanhas = $query->orderBy($sort, $order)
                ->skip($offset)
                ->take($limit)
                ->get()
                ->map(function ($campanha) {
                    return [
                        'id' => $campanha->id,
                        'nome' => $campanha->nome,
                        'horario' => $campanha->horario,
                        'data' => $campanha->data ? $campanha->data->format('d/m/Y') : 'Envio Diário',
                        'ultima_execucao' => $campanha->ultima_execucao ? \Carbon\Carbon::parse($campanha->ultima_execucao)->format('d/m/Y H:i') : 'N/A',
                        'contatos_count' => $campanha->contatos_count,
                        'status' => $this->getStatus($campanha),
                        'created_at' => $campanha->created_at->format('d/m/Y H:i:s'),
                        'updated_at' => $campanha->updated_at->format('d/m/Y H:i:s'),
                        'user_name' => $campanha->user->name ?? 'N/A',
                        'actions' => $this->getActionsHtml($campanha)
                    ];
                });
    
            return response()->json([
                'total' => $total,
                'rows' => $campanhas
            ]);
    
        } catch (\Exception $e) {
            Log::error("Erro ao exibir campanhas: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao carregar campanhas: ' . $e->getMessage()], 500);
        }
    }

    private function getActionsHtml($campanha)
    {
        return view('campanhas.partials.actions', compact('campanha'))->render();
    }

    private function getStatus($campanha)
    {
        if ($campanha->enviar_diariamente) {
            return '<span class="badge bg-info">Recorrente</span>';
        }
        
        if (!$campanha->data || !$campanha->horario) {
            return '<span class="badge bg-warning">Pendente</span>';
        }
        
        try {
            $dataHora = \Carbon\Carbon::parse($campanha->data->format('Y-m-d') . ' ' . $campanha->horario);
            return $dataHora < now() 
                ? '<span class="badge bg-secondary">Enviada</span>'
                : '<span class="badge bg-primary">Agendada</span>';
        } catch (\Exception $e) {
            return '<span class="badge bg-warning">Pendente</span>';
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
            'arquivo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
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
    
    public function destroy($id)
    {
        $campanha = Campanha::findOrFail($id);
        if ($campanha->arquivo) {
            Storage::disk('public')->delete($campanha->arquivo);
        }
        $campanha->delete();

        return redirect()->route('campanhas.index')->with('success', 'Campanha excluída com sucesso!');
    }
    public function updateUserPreferences()
{
    $user = Auth::user();
    $preferences = DB::table('user_client_preferences')
        ->where('user_id', $user->id)
        ->where('table_name', 'campanhas')
        ->first();

    if ($preferences) {
        $columns = json_decode($preferences->visible_columns, true);
        if (!in_array('ultima_execucao', $columns)) {
            $columns[] = 'ultima_execucao';
            DB::table('user_client_preferences')
                ->where('id', $preferences->id)
                ->update(['visible_columns' => json_encode($columns)]);
        }
    }
}
}
