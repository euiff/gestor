@extends('layouts/layoutMaster')

@section('title', 'Planos de Renovação')

@php
    $visibleColumns = getUserPreferences('planos_renovacao');
    $type = 'planos_renovacao';
@endphp

@section('page-script')
<script>
    var loadDataUrl = '{{ route('planos-renovacao.list') }}';
    var destroyMultipleUrl = '{{ route('planos-renovacao.destroy') }}';
    var label_update = '{{ __('messages.update') }}';
    var label_delete = '{{ __('messages.delete') }}';

</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('assets/js/pages/planos-renovacao.js') }}"></script>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Verificação de Mensagens de Sessão -->
        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- mensagens para erros -->
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- mensagens para sucesso -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <h4 class="py-3 mb-2">
            <span class="text-muted fw-light">{{ config('variables.templateName', 'TemplateName') }} / </span>Renovação de Planos
        </h4>

        <!-- Botão para abrir o modal de adicionar plano de renovação -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addPlanoRenovacao">Adicionar Plano de Renovação</button>



        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <input type="hidden" id="data_type" value="planos_renovacao">
                    <input type="hidden" id="save_column_visibility" name="visible_columns">
                    <div class="fixed-table-toolbar">
                    </div>
                    <table id="table" data-toggle="table" data-loading-template="loadingTemplate"
                        data-url="{{ route('planos-renovacao.list') }}" data-icons-prefix="bx" data-icons="icons"
                        data-show-refresh="true" data-total-field="total" data-trim-on-search="false"
                        data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                        data-side-pagination="server" data-show-columns="true" data-pagination="true"
                        data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                        data-query-params="queryParams"
                        data-route-prefix="{{ Route::getCurrentRoute()->getPrefix() }}">
                        <thead>
                            <tr>
                                <th data-checkbox="true"></th>
                                <th data-sortable="true" data-field="id">ID</th>
                                <th data-field="nome" data-visible="{{ in_array('nome', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Nome</th>
                                <th data-field="descricao" data-visible="{{ in_array('descricao', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Descrição</th>
                                <th data-field="preco" data-visible="{{ in_array('preco', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Preço</th>
                                <th data-field="detalhes" data-visible="{{ in_array('detalhes', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Detalhes</th>
                                <th data-field="botao" data-visible="{{ in_array('botao', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Botão</th>
                                <th data-field="limite" data-visible="{{ in_array('limite', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Limite</th>
                                <th data-field="actions" data-visible="{{ in_array('actions', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}">Ações</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>


       <!-- Modal para adicionar novo plano de renovação -->
    <div class="modal fade" id="addPlanoRenovacao" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-simple modal-add-plano-renovacao">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h3 class="mb-2">Adicionar Novo Plano de Renovação</h3>
                        <p class="text-muted">Preencha os detalhes do novo plano de renovação.</p>
                    </div>
                    <form id="addPlanoRenovacaoForm" class="row g-3" action="{{ route('planos-renovacao.store') }}" method="POST">
                        @csrf
                        <div class="col-12">
                            <label class="form-label" for="addPlanoRenovacaoTipo">Tipo de Plano</label>
                            <select id="addPlanoRenovacaoTipo" name="tipo" class="form-select" required>
                                <option value="">Selecione um Plano</option>
                                <option value="basic">Plano Básico</option>
                                <option value="pro">Plano Pro</option>
                                <option value="enterprise">Plano Enterprise</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="addPlanoRenovacaoNome">Nome</label>
                            <input type="text" id="addPlanoRenovacaoNome" name="nome" class="form-control" placeholder="Nome do Plano" required />
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="addPlanoRenovacaoDescricao">Descrição</label>
                            <textarea id="addPlanoRenovacaoDescricao" name="descricao" class="form-control" placeholder="Descrição do Plano"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="addPlanoRenovacaoPreco">Preço</label>
                            <input type="number" step="0.01" id="addPlanoRenovacaoPreco" name="preco" class="form-control" placeholder="Preço do Plano" required />
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="addPlanoRenovacaoDetalhes">Detalhes</label>
                            <textarea id="addPlanoRenovacaoDetalhes" name="detalhes" class="form-control" placeholder="Detalhes do Plano"></textarea>
                        </div>
                        {{-- <div class="col-12">
                            <label class="form-label" for="duracao">Duração</label>
                            <select id="duracao" name="duracao" class="form-select" required>
                                <option value="1">1 mês</option>
                                <option value="3">3 meses</option>
                                <option value="6">6 meses</option>
                                <option value="12">12 meses</option>
                            </select>
                        </div> --}}
                        <div class="col-12">
                            <label class="form-label" for="addPlanoRenovacaoBotao">Botão</label>
                            <input type="text" id="addPlanoRenovacaoBotao" name="botao" class="form-control" placeholder="Texto do Botão" />
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="addPlanoRenovacaoLimite">Limite</label>
                            <input type="number" id="addPlanoRenovacaoLimite" name="limite" class="form-control" placeholder="Limite do Plano" required />
                        </div>
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Adicionar</button>
                            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="confirmDeleteSelectedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel2">Aviso!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza de que deseja excluir o(s) registro(s) selecionado(s)?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Fechar
                    </button>
                    <button type="submit" class="btn btn-danger" id="confirmDeleteSelections">Sim</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação para Salvar Visibilidade das Colunas -->
    <div class="modal fade" id="confirmSaveColumnVisibility" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Salvar Visibilidade das Colunas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza de que deseja salvar as preferências de visibilidade das colunas?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirm">Salvar</button>
                </div>
            </div>
        </div>
    </div>
@endsection