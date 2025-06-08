@extends('layouts/layoutMaster')

@section('title', 'Planos')

@php
    $visibleColumns = getUserPreferences('planos');
    $type = 'planos';
@endphp

@section('page-script')
<script>
    var loadDataUrl = '{{ route('planos.list') }}';
    var destroyMultipleUrl = '{{ route('planos.destroy_multiple') }}';
    var label_update = '{{ __('messages.update') }}';
    var label_delete = '{{ __('messages.delete') }}';
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('assets/js/pages/planos.js') }}"></script>
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
            <span class="text-muted fw-light">{{ config('variables.templateName', 'TemplateName') }} / </span> Planos
        </h4>

        <!-- Botão para abrir o modal de adicionar plano -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addPlano">   <i class='bx bx-plus'></i>Adicionar Plano</button>

        <!-- Tabela de Planos -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <input type="hidden" id="data_type" value="planos">
                    <input type="hidden" id="save_column_visibility" name="visible_columns">
                    <div class="fixed-table-toolbar">
                    </div>
                    <table id="table" data-toggle="table" data-loading-template="loadingTemplate"
                        data-url="{{ route('planos.list') }}" data-icons-prefix="bx" data-icons="icons"
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
                                <th data-field="preco" data-visible="{{ in_array('preco', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Preço</th>
                                <th data-field="duracao" data-visible="{{ in_array('duracao', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Duração (dias)</th>
                                <th data-field="user_name" data-visible="{{ in_array('user_name', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Usuário</th>
                                <th data-field="actions" data-visible="{{ in_array('actions', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}">Ações</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal para adicionar novo plano -->
        <div class="modal fade" id="addPlano" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-simple modal-add-plano">
                <div class="modal-content p-3 p-md-5">
                    <div class="modal-body">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Adicionar Novo Plano</h3>
                            <p class="text-muted">Preencha os detalhes do novo plano.</p>
                        </div>
                        <form id="addPlanoForm" class="row g-3" action="{{ route('planos.store') }}" method="POST">
                            @csrf
                            <div class="col-12">
                                <label class="form-label" for="addPlanoNome">Nome</label>
                                <input type="text" id="addPlanoNome" name="nome" class="form-control" placeholder="Nome do Plano" required />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="addPlanoPreco">Preço</label>
                                <input type="number" step="0.01" id="addPlanoPreco" name="preco" class="form-control" placeholder="Preço do Plano" required />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="addPlanoDuracao">Duração (dias)</label>
                                <input type="number" id="addPlanoDuracao" name="duracao" class="form-control" placeholder="Duração do Plano em dias" required />
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
@endsection