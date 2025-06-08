<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campanha;
use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\SendMessageController;
use Illuminate\Http\Request;

class DispararCampanhas extends Command
{
    protected $signature = 'campanhas:disparar';
    protected $description = 'Dispara campanhas de cobrança para clientes';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $hoje = Carbon::now();
        $dataAtual = $hoje->toDateString();
        $horaAtual = $hoje->format('H:i');
    
        Log::info('Disparando campanhas para ' . $hoje->toDateString() . ' às ' . $horaAtual);
    
        // Campanhas com data e horário específicos
        $campanhasComDataEHorario = Campanha::whereDate('data', $dataAtual)
            ->where('horario', $horaAtual)
            ->get();
    
        // Campanhas com horário apenas
        $campanhasComHorarioApenas = Campanha::whereNull('data')
            ->where('horario', $horaAtual)
            ->get();
    
        // Mesclar as campanhas
        $campanhas = $campanhasComDataEHorario->merge($campanhasComHorarioApenas);
    
        foreach ($campanhas as $campanha) {
            // Verificar se a campanha já foi executada hoje e se não deve ser enviada diariamente
            if ($campanha->ultima_execucao) {
                $ultimaExecucao = Carbon::parse($campanha->ultima_execucao);
                if ($ultimaExecucao->isToday() && !$campanha->enviar_diariamente) {
                    Log::info('Campanha já enviada hoje: ' . $campanha->nome);
                    continue;
                }
            }
    
            Log::info('Processando campanha: ' . $campanha->nome);
            Log::debug('Dados da campanha:', [
                'id' => $campanha->id,
                'origem_contatos' => $campanha->origem_contatos,
                'contatos' => $campanha->contatos,
                'contatos_count' => is_array($campanha->contatos) ? count($campanha->contatos) : 0
            ]);
    
            if (empty($campanha->contatos)) {
                Log::warning('Campanha sem contatos definidos: ' . $campanha->id);
                continue;
            }
    
                    // Obter clientes com base na origem dos contatos
            switch ($campanha->origem_contatos) {
                case 'todos':
                    Log::info('Obtendo todos os clientes para o usuário: ' . $campanha->user_id);
                    $clientes = Cliente::where('user_id', $campanha->user_id)->get();
                    break;
                case 'vencidos':
                    $hoje = now()->format('Y-m-d');
                    Log::info('Obtendo clientes vencidos para o usuário: ' . $campanha->user_id . ' até a data: ' . $hoje);
                    $clientes = Cliente::where('user_id', $campanha->user_id)
                                        ->where('vencimento', '<', $hoje)
                                        ->get();
                    break;
                case 'vencem_hoje':
                    $hoje = now()->format('Y-m-d');
                    Log::info('Obtendo clientes que vencem hoje para o usuário: ' . $campanha->user_id . ' na data: ' . $hoje);
                    $clientes = Cliente::where('user_id', $campanha->user_id)
                                        ->where('vencimento', $hoje)
                                        ->get();
                    break;
                case 'ativos':
                    Log::info('Obtendo todos os clientes ativos para o usuário: ' . $campanha->user_id);
                    $clientes = Cliente::where('user_id', $campanha->user_id)->get();
                    break;
                case 'servidores':
                    Log::info('Obtendo clientes dos servidores para o usuário: ' . $campanha->user_id);
                    $clientes = Cliente::whereIn('id', $campanha->contatos)->get();
                    break;
                case 'manual':
                case 'clientes': // Adicione este caso
                    Log::info('Obtendo clientes específicos para a campanha: ' . $campanha->id);
                    $clientes = Cliente::whereIn('id', $campanha->contatos)->get();
                    break;
                default:
                    Log::info('Origem de contatos desconhecida: ' . $campanha->origem_contatos);
                    $clientes = collect(); // Coleção vazia
                    break;
            }
    
            Log::debug('Clientes encontrados:', [
                'count' => $clientes->count(),
                'clientes_ids' => $clientes->pluck('id')
            ]);
    
            if (empty($campanha->contatos)) {
                Log::warning('Campanha sem contatos definidos: ' . $campanha->id);
                continue;
            }
            
            // Verifique se os IDs dos contatos existem na tabela de clientes
            $contatosExistentes = Cliente::whereIn('id', $campanha->contatos)->exists();
            if (!$contatosExistentes) {
                Log::error('IDs de contatos não encontrados na tabela clientes', [
                    'campanha_id' => $campanha->id,
                    'contatos' => $campanha->contatos
                ]);
                continue;
            }
    
            foreach ($clientes as $cliente) {
                $this->dispararCampanhaParaCliente($campanha, $cliente);
            }
    
            // Atualizar a última execução da campanha
            $campanha->ultima_execucao = Carbon::now();
            $campanha->save();
    
            $this->info('Campanha disparada: ' . $campanha->nome);
            Log::info('Campanha disparada com sucesso: ' . $campanha->nome);
        }
    }

    protected function dispararCampanhaParaCliente($campanha, $cliente)
    {
        $sendMessageController = new SendMessageController();
        
        $dadosCliente = [
            '{nome_cliente}' => $cliente->nome ?? 'Nome do Cliente',
            '{telefone_cliente}' => $cliente->whatsapp ?? '(11) 99999-9999',
            '{notas}' => $cliente->notas ?? 'Notas',
            '{vencimento_cliente}' => Carbon::parse($cliente->vencimento)->format('d/m/Y') ?? 'Vencimento do Cliente',
            '{data_atual}' => Carbon::now()->format('d/m/Y'),
        ];
    
        $conteudoCliente = $this->substituirPlaceholders($campanha->mensagem, $dadosCliente);
    
        Log::info('Enviando mensagem para ' . $cliente->whatsapp);
        Log::info('Mensagem: ' . $conteudoCliente);
        
        $requestData = [
            'phone' => $cliente->whatsapp,
            'message' => $conteudoCliente,
            'user_id' => $campanha->user_id,
        ];
        
        // Adiciona a imagem se existir
        if (!empty($campanha->arquivo)) {
            $requestData['image'] = $campanha->arquivo;
        }
        
        $sendMessageController->sendMessageWithoutAuth(new Request($requestData));
        
        Log::info('Mensagem enviada para ' . $cliente->whatsapp);
    }

    private function substituirPlaceholders($conteudo, $dados)
    {
        foreach ($dados as $placeholder => $valor) {
            $conteudo = str_replace($placeholder, $valor, $conteudo);
        }

        return $conteudo;
    }
}