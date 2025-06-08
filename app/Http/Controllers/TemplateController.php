<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ConexaoController;
use App\Models\PlanoRenovacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TemplateController extends Controller
{
public function index(Request $request, $user_id = null)
{
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $user = Auth::user();
    $userId = $user->id;
    $userRole = $user->role->name;

    $ensureUtf8 = function ($string) {
        return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    };

    if ($userRole === 'admin') {
        $filter = $request->input('filter', 'all');

        $templates = DB::table('templates')
            ->join('users', 'templates.user_id', '=', 'users.id')
            ->select('templates.*', 'users.name as user_name')
            ->when($filter == 'mine', function ($query) use ($userId) {
                return $query->where('templates.user_id', $userId);
            })
            ->when($filter == 'others', function ($query) use ($userId) {
                return $query->where('templates.user_id', '!=', $userId);
            })
            ->get()
            ->map(function ($template) use ($ensureUtf8) {
                foreach ($template as $key => $value) {
                    if (is_string($value)) {
                        $template->{$key} = $ensureUtf8($value);
                    }
                }
                return $template;
            });
    } else {
        $templates = DB::table('templates')
            ->join('users', 'templates.user_id', '=', 'users.id')
            ->select('templates.*', 'users.name as user_name')
            ->where('templates.user_id', $userId)
            ->get()
            ->map(function ($template) use ($ensureUtf8) {
                foreach ($template as $key => $value) {
                    if (is_string($value)) {
                        $template->{$key} = $ensureUtf8($value);
                    }
                }
                return $template;
            });
    }

    $current_plan_id = $user->plano_id;
    $planos_revenda = PlanoRenovacao::all();

    return view('templates.index', compact(
        'templates',
        'current_plan_id',
        'planos_revenda',
        'user'
    ));
}
    
public function list(Request $request)
{
    Log::info('Acessando a listagem de templates com paginação e busca.');

    try {
        if (Auth::check()) {
            $user = Auth::user();
            $userRole = $user->role->name;

            $search = $request->input('search');
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'DESC');

            // Todos os usuários, incluindo administradores, veem apenas seus próprios dados
            $templates = Template::where('user_id', $user->id);

            if ($search) {
                $templates = $templates->where(function ($query) use ($search) {
                    $query->where('nome', 'like', '%' . $search . '%')
                          ->orWhere('finalidade', 'like', '%' . $search . '%');
                });
            }

            $totalTemplates = $templates->count();
            $canEdit = true;
            $canDelete = true;

            // Primeiro obtenha os templates paginados
            $templates = $templates->orderBy($sort, $order)
                                   ->paginate($request->input('limit', 10));

            // Processe os dados garantindo que estejam em UTF-8
            $processedTemplates = $templates->getCollection()->map(function ($template) use ($canEdit, $canDelete) {
                // Limita o conteúdo a 30 caracteres e garante UTF-8
                $conteudo = mb_convert_encoding($template->conteudo, 'UTF-8', 'UTF-8');
                $conteudoTruncado = mb_strlen($conteudo) > 30
                    ? mb_substr($conteudo, 0, 30) . '...'
                    : $conteudo;

                // Define se os botões de ação devem ser exibidos
                $showActions = mb_strlen($conteudo) > 5;

                // Gera os botões de ação
                $actions = $showActions
                    ? '<div class="d-grid gap-3">
                          <div class="row g-3">
                              <div class="col-6 mb-2">
                                  <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#editTemplate' . $template->id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                                      <i class="fas fa-edit"></i>
                                  </button>
                              </div>
                              <div class="col-6 mb-2">
                                  <form action="' . route('templates.destroy', $template->id) . '" method="POST" style="display:inline;">
                                      ' . csrf_field() . '
                                      ' . method_field('DELETE') . '
                                      <button type="submit" class="btn btn-sm btn-danger w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Deletar">
                                          <i class="fas fa-trash-alt"></i>
                                      </button>
                                  </form>
                              </div>
                          </div>
                      </div>'
                    : '';

                return [
                    'id' => $template->id,
                    'nome' => mb_convert_encoding($template->nome, 'UTF-8', 'UTF-8'),
                    'finalidade' => mb_convert_encoding($template->finalidade, 'UTF-8', 'UTF-8'),
                    'conteudo' => $conteudoTruncado,
                    'user_name' => $template->user ? mb_convert_encoding($template->user->name, 'UTF-8', 'UTF-8') : 'N/A',
                    'actions' => $actions,
                    'show_actions' => $showActions
                ];
            });

            // Substitui a coleção original pela processada
            $templates->setCollection($processedTemplates);

            // Fetch user preferences for visible columns
            $userId = $user->id;
            $preferences = DB::table('user_client_preferences')
                ->where('user_id', $userId)
                ->where('table_name', 'templates')
                ->value('visible_columns');

            $visibleColumns = json_decode($preferences, true) ?: [
                'id',
                'nome',
                'finalidade',
                'conteudo',
                'user_name',
                'actions'
            ];

            // Filter the columns based on user preferences
            $filteredTemplates = $templates->map(function ($template) use ($visibleColumns) {
                return array_filter($template, function ($key) use ($visibleColumns) {
                    return in_array($key, $visibleColumns);
                }, ARRAY_FILTER_USE_KEY);
            });

            return response()->json([
                'rows' => $filteredTemplates,
                'total' => $totalTemplates,
            ]);
        } else {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
    } catch (\Exception $e) {
        Log::error('Erro ao acessar a listagem de templates: ' . $e->getMessage());
        return response()->json(['error' => 'Erro ao acessar a listagem de templates'], 500);
    }
}
    public function show($id)
    {
        // Método vazio para evitar erro
    }

    public function store(Request $request)
    {
        Log::info('Iniciando processo de salvamento do template.');
    
        // Validação dos campos
        $request->validate([
            'nome' => 'required|string|max:255',
            'finalidade' => 'required|string|max:255',
            'tipo_mensagem' => 'required|string|in:texto,texto_com_imagem',
            'conteudo' => 'required_if:tipo_mensagem,texto|nullable|string',
            'imagem' => 'required_if:tipo_mensagem,texto_com_imagem|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
    
        Log::info('Validação dos campos concluída.');
    
        // Verificar se já existe um template com a mesma finalidade para este usuário
        $existingTemplate = Template::where('user_id', Auth::id())
                                  ->where('finalidade', $request->finalidade)
                                  ->first();
    
        if ($existingTemplate) {
            Log::warning('Usuário já possui um template com esta finalidade.', [
                'user_id' => Auth::id(),
                'finalidade' => $request->finalidade,
                'existing_template_id' => $existingTemplate->id
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Você já possui um template cadastrado para a finalidade "' . $request->finalidade . '".');
        }
    
        // Dados básicos do template
        $data = [
            'user_id' => Auth::id(),
            'nome' => $request->nome,
            'finalidade' => $request->finalidade,
            'tipo_mensagem' => $request->tipo_mensagem,
            'conteudo' => $request->conteudo,
        ];
    
        Log::info('Dados básicos do template preparados:', $data);
    
        // Processar upload da imagem, se for uma mensagem com imagem
        if ($request->hasFile('imagem') && $request->tipo_mensagem === 'texto_com_imagem') {
            Log::info('Processando upload da imagem...');
    
            $directory = public_path('assets/img/templates');
            if (!is_dir($directory)) {
                Log::info('Diretório não existe. Criando diretório: ' . $directory);
                mkdir($directory, 0777, true);
            }
            chmod($directory, 0777);
    
            // Obter o nome original do arquivo
            $originalFileName = $request->file('imagem')->getClientOriginalName();
            Log::info('Nome original do arquivo da imagem: ' . $originalFileName);
    
            // Remover espaços e caracteres especiais do nome do arquivo
            $fileName = str_replace(' ', '_', $originalFileName);
            $fileName = preg_replace('/[^A-Za-z0-9_.-]/', '', $fileName);
            $fileName = time() . '_' . $fileName;
    
            Log::info('Nome do arquivo tratado: ' . $fileName);
    
            // Mover a imagem para o diretório
            $path = $request->file('imagem')->move($directory, $fileName);
            if ($path) {
                $data['imagem'] = '/assets/img/templates/' . $fileName;
                Log::info('Imagem salva com sucesso. Caminho: ' . $data['imagem']);
            } else {
                Log::error('Erro ao mover a imagem para o diretório.');
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Erro ao salvar a imagem. Por favor, tente novamente.');
            }
        }
    
        // Criar o template no banco de dados
        try {
            Log::info('Tentando salvar o template no banco de dados...');
            Template::create($data);
            Log::info('Template salvo com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao salvar o template: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao salvar o template: ' . $e->getMessage());
        }
    
        return redirect()->route('templates.index')
            ->with('success', 'Template criado com sucesso.');
    }

    public function update(Request $request, $id)
    {
        Log::info('Iniciando processo de atualização do template.', ['template_id' => $id]);
    
        // Buscar o template existente primeiro para verificar o tipo atual
        try {
            Log::info('Buscando template no banco de dados...');
            $template = Template::findOrFail($id);
            Log::info('Template encontrado:', ['template' => $template]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar template:', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Template não encontrado.');
        }
    
        // Validação condicional dos campos
        try {
            Log::info('Validando campos do formulário...');
            
            $validationRules = [
                'nome' => 'required|string|max:255',
                'finalidade' => 'required|string|max:255',
                'tipo_mensagem' => 'required|string|in:texto,texto_com_imagem',
                'conteudo' => 'required|string',
            ];
    
            // Só exige imagem se:
            // 1. Está criando um novo template com imagem OU
            // 2. Está alterando um template existente de texto para texto_com_imagem
            if ($request->tipo_mensagem === 'texto_com_imagem' && 
                ($template->tipo_mensagem !== 'texto_com_imagem' || !$template->imagem)) {
                $validationRules['imagem'] = 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048';
            } else {
                $validationRules['imagem'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048';
            }
    
            $request->validate($validationRules);
            Log::info('Validação dos campos concluída com sucesso.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Erro na validação dos campos:', ['errors' => $e->errors()]);
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
    
        // Dados básicos do template
        $data = [
            'nome' => $request->nome,
            'finalidade' => $request->finalidade,
            'tipo_mensagem' => $request->tipo_mensagem,
            'conteudo' => $request->conteudo,
        ];
    
        // Processar imagem apenas se necessário
        if ($request->tipo_mensagem === 'texto_com_imagem') {
            // Se foi enviada uma nova imagem
            if ($request->hasFile('imagem')) {
                Log::info('Processando upload da nova imagem...');
    
                $directory = public_path('assets/img/templates');
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
    
                $originalFileName = $request->file('imagem')->getClientOriginalName();
                $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '', str_replace(' ', '_', $originalFileName));
    
                try {
                    $path = $request->file('imagem')->move($directory, $fileName);
                    
                    // Remove a imagem antiga se existir
                    if ($template->imagem && file_exists(public_path($template->imagem))) {
                        unlink(public_path($template->imagem));
                    }
                    
                    $data['imagem'] = '/assets/img/templates/' . $fileName;
                } catch (\Exception $e) {
                    Log::error('Erro ao processar imagem:', ['error' => $e->getMessage()]);
                    return redirect()->back()->with('error', 'Erro ao processar a imagem.');
                }
            } elseif ($template->tipo_mensagem === 'texto_com_imagem' && $template->imagem) {
                // Mantém a imagem existente se não foi enviada uma nova
                $data['imagem'] = $template->imagem;
                Log::info('Mantendo imagem existente: ' . $data['imagem']);
            }
        } else {
            // Se está mudando para texto, remove a imagem se existir
            if ($template->imagem && file_exists(public_path($template->imagem))) {
                unlink(public_path($template->imagem));
            }
            $data['imagem'] = null;
        }
    
        // Atualizar o template
        try {
            $template->update($data);
            Log::info('Template atualizado com sucesso.');
            return redirect()->route('templates.index')->with('success', 'Template atualizado com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar template:', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Erro ao atualizar o template.');
        }
    }

    public function destroy($id)
    {
        $template = Template::findOrFail($id);
        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Template deletado com sucesso.');
    }

    public function destroy_multiple(Request $request)
    {
        $ids = $request->input('ids');
        if (is_array($ids)) {
            Template::whereIn('id', $ids)->delete();
            return response()->json(['error' => false, 'message' => 'Templates excluídos com sucesso.']);
        } else {
            return response()->json(['error' => true, 'message' => 'Nenhum template selecionado.']);
        }
    }

    public function enviarMensagem($templateId, $clienteId, $tipoMensagem)
    {
        Log::info("Iniciando envio de mensagem. Template ID: {$templateId}, Cliente ID: {$clienteId}, Tipo: {$tipoMensagem}");
    
        try {
            // Buscar Template e Cliente
            $template = Template::findOrFail($templateId);
            Log::info("Template encontrado: ID {$template->id}, Nome: {$template->nome}");
    
            $cliente = Cliente::findOrFail($clienteId);
            Log::info("Cliente encontrado: Nome {$cliente->nome}, Telefone {$cliente->whatsapp}");
    
            // Formatando os dados do cliente para substituição no template
            $dadosCliente = [
                'nome' => $cliente->nome,
                'iptv_nome' => $cliente->iptv_nome,
                'iptv_senha' => $cliente->iptv_senha,
                'telefone' => preg_replace('/[^0-9]/', '', $cliente->whatsapp),
                'notas' => $cliente->notas,
                'vencimento' => $cliente->vencimento,
                'plano_nome' => $cliente->plano->nome,
                'plano_valor' => $cliente->plano->valor,
                'plano_link' => $cliente->plano_link,
                'text_expirate' => $this->getTextExpirate($cliente->vencimento),
            ];
            Log::debug("Dados do cliente preparados para substituição de placeholders.", $dadosCliente);
    
            // Se o tipo de mensagem for "texto_com_imagem", adiciona o caminho da imagem
            if ($template->tipo_mensagem === 'texto_com_imagem') {
                $dadosCliente['imagem'] = $template->imagem;
                Log::info("Template é do tipo 'texto com imagem'. Caminho da imagem: " . $template->imagem);
            }
    
            // Substitui os placeholders no conteúdo do template
            $conteudo = $this->substituirPlaceholders($template->conteudo, $dadosCliente);
            Log::info("Conteúdo gerado para envio.");
    
            // Criar instância do controlador de conexões
            $conexaoController = new ConexaoController();
            Log::info("Instância do ConexaoController criada.");
    
            // Prepara os dados para envio
            $requestData = [
                'phone' => $dadosCliente['telefone'],
                'message' => $conteudo,
            ];
            Log::debug("Dados preparados para envio da mensagem.", $requestData);
    
            // Se for mensagem com imagem, adiciona ao request
            if ($template->tipo_mensagem === 'texto_com_imagem' && !empty($template->imagem)) {
                $requestData['image'] = $template->imagem;
                Log::info("Imagem adicionada ao request.", ['image' => $template->imagem]);
            }
    
            // Envio da mensagem
            Log::info("Iniciando envio da mensagem...");
            $response = $conexaoController->sendMessage(new Request($requestData));
    
            // Log da resposta da API
            Log::info("Resposta da API.", ['status' => $response->getStatusCode(), 'body' => $response->getContent()]);
    
            // Verifica se a mensagem foi enviada com sucesso
            if ($response->getStatusCode() === 200) {
                Log::info("Mensagem enviada com sucesso.");
                return redirect()->route('app-ecommerce-customer-all')->with('success', 'Mensagem enviada com sucesso.');
            } else {
                Log::error("Erro ao enviar mensagem. Status code: " . $response->getStatusCode());
                return redirect()->route('app-ecommerce-customer-all')->with('error', 'Erro ao enviar mensagem.');
            }
        } catch (\Exception $e) {
            Log::error("Erro ao enviar mensagem: " . $e->getMessage());
            return redirect()->route('app-ecommerce-customer-all')->with('error', 'Erro ao enviar mensagem.');
        }
    }
    
    
    public function cobrancaManual($clienteId)
    {
        Log::info('Iniciando processo de cobrança manual para o cliente ID: ' . $clienteId);
    
        $cliente = Cliente::findOrFail($clienteId);
        Log::info('Cliente encontrado: ' . $cliente->nome);
    
        // Supondo que você tenha um template específico para cobrança manual
        $template = Template::where('finalidade', 'cobranca_manual')->firstOrFail();
        Log::info('Template de cobrança manual encontrado: ' . $template->nome);
    
        // Dados do cliente para substituição de placeholders
        $dadosCliente = [
            'nome' => $cliente->nome,
            'iptv_nome' => $cliente->iptv_nome,
            'iptv_senha' => $cliente->iptv_senha,
            'telefone' => preg_replace('/[^0-9]/', '', $cliente->whatsapp), // Remove tudo que não for número
            'notas' => $cliente->notas,
            'vencimento' => $cliente->vencimento,
            'plano_nome' => $cliente->plano->nome,
            'plano_valor' => $cliente->plano->valor,
            'plano_link' => $cliente->plano_link,
            'text_expirate' => $this->getTextExpirate($cliente->vencimento),
        ];
        Log::info('Dados do cliente preparados para substituição de placeholders:', $dadosCliente);
    
        // Se o tipo de mensagem for "texto_com_imagem", adiciona o caminho da imagem aos dados do cliente
        if ($template->tipo_mensagem === 'texto_com_imagem') {
            $dadosCliente['imagem'] = $template->imagem; // Caminho da imagem
            Log::info('Template é do tipo "texto com imagem". Caminho da imagem: ' . $template->imagem);
        }
    
        // Substitui os placeholders no conteúdo do template
        $conteudo = $this->substituirPlaceholders($template->conteudo, $dadosCliente);
        Log::info('Placeholders substituídos no conteúdo do template.');
    
        // Envia a mensagem via ConexaoController
        $conexaoController = new ConexaoController();
        Log::info('Instância do ConexaoController criada.');
    
        // Prepara os dados para enviar a mensagem
        $requestData = [
            'phone' => preg_replace('/[^0-9]/', '', $cliente->whatsapp), // Remove tudo que não for número
            'message' => $conteudo,
        ];
        Log::info('Dados preparados para envio da mensagem:', $requestData);
    
        // Se for uma mensagem com imagem, adiciona o caminho da imagem ao request
        if ($template->tipo_mensagem === 'texto_com_imagem' && !empty($template->imagem)) {
            $requestData['image'] = $template->imagem; // Caminho da imagem
            Log::info('Imagem adicionada ao request:', ['image' => $template->imagem]);
        }
    
        // Envia a mensagem
        Log::info('Iniciando envio da mensagem...');
        $response = $conexaoController->sendMessage(new Request($requestData));
        Log::info('Resposta da API:', ['status' => $response->getStatusCode(), 'body' => $response->getContent()]);
    
        // Verifica se a mensagem foi enviada com sucesso
        if ($response->getStatusCode() === 200) {
            Log::info('Mensagem enviada com sucesso.');
            return redirect()->route('app-ecommerce-customer-all')->with('success', 'Cobrança manual enviada com sucesso.');
        } else {
            Log::error('Erro ao enviar mensagem. Status code: ' . $response->getStatusCode());
            return redirect()->route('app-ecommerce-customer-all')->with('error', 'Erro ao enviar cobrança manual.');
        }
    }

    private function substituirPlaceholders($conteudo, $dadosCliente)
    {
        // Placeholders padrão
        $placeholders = [
            '{nome_cliente}' => $dadosCliente['nome'] ?? 'Nome do Cliente',
            '{iptv_nome}' => $dadosCliente['iptv_nome'] ?? 'Usuário IPTV',
            '{iptv_senha}' => $dadosCliente['iptv_senha'] ?? 'Senha IPTV',
            '{telefone_cliente}' => $dadosCliente['telefone'] ?? '(11) 99999-9999',
            '{notas}' => $dadosCliente['notas'] ?? 'Notas do cliente',
            '{vencimento_cliente}' => $dadosCliente['vencimento'] ?? '01/01/2023',
            '{plano_nome}' => $dadosCliente['plano_nome'] ?? 'Plano Básico',
            '{plano_valor}' => $dadosCliente['plano_valor'] ?? 'R$ 99,90',
            '{data_atual}' => date('d/m/Y'),
            '{plano_link}' => $dadosCliente['plano_link'] ?? 'http://linkdopagamento.com', // Apenas o link
            '{text_expirate}' => $this->getTextExpirate($dadosCliente['vencimento']),
            '{saudacao}' => $this->getSaudacao(),
        ];
    
        // Adicionar o placeholder da imagem, se existir
        if (isset($dadosCliente['imagem'])) {
            $placeholders['{imagem}'] = $dadosCliente['imagem']; // Caminho da imagem
        } else {
            $placeholders['{imagem}'] = ''; // Se não houver imagem, substitui por uma string vazia
        }
    
        // Substituir os placeholders no conteúdo
        foreach ($placeholders as $placeholder => $valor) {
            $conteudo = str_replace($placeholder, $valor, $conteudo);
        }
    
        return $conteudo;
    }

    private function getTextExpirate($vencimento)
    {
        $dataVencimento = new \DateTime($vencimento);
        $dataAtual = new \DateTime();
        $intervalo = $dataAtual->diff($dataVencimento);

        if ($intervalo->invert) {
            return 'expirou há ' . $intervalo->days . ' dias';
        } elseif ($intervalo->days == 0) {
            return 'expira hoje';
        } else {
            return 'expira em ' . $intervalo->days . ' dias';
        }
    }

    private function getSaudacao()
    {
        $hora = date('H');
        if ($hora < 12) {
            return 'Bom dia!';
        } elseif ($hora < 18) {
            return 'Boa tarde!';
        } else {
            return 'Boa noite!';
        }
    }
}