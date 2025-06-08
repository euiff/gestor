@extends('layouts/layoutMaster')
@section('title', 'Servidores')

@php
    $visibleColumns = getUserPreferences('servidores');
@endphp

@section('page-script')
<script>
    var destroyMultipleUrl = '{{ route('servidores.deletarMultiplos') }}';
    var label_update = '{{ __('messages.update') }}';
    var label_delete = '{{ __('messages.delete') }}';
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('assets/js/pages/servidores.js') }}"></script>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mt-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            {{-- <a href="{{ route('home.index') }}">Início</a> --}}
                        </li>
                        <li class="breadcrumb-item active">
                            Servidores
                        </li>
                    </ol>
                </nav>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addServidor">
                    <i class='bx bx-plus'></i> Adicionar Servidor
                </button>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="row">
                </div>

                <div class="card-body">
                    <div class="table-responsive text-nowrap">
                        <input type="hidden" id="data_type" value="servidores">
                        <input type="hidden" id="save_column_visibility" name="visible_columns">
                        <div class="fixed-table-toolbar">
                        </div>
                        <table id="table" data-toggle="table" data-loading-template="loadingTemplate"
                            data-url="{{ route('servidores.list') }}" data-icons-prefix="bx" data-icons="icons"
                            data-show-refresh="true" data-total-field="total" data-trim-on-search="false"
                            data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                            data-side-pagination="server" data-show-columns="true" data-pagination="true"
                            data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                            data-query-params="queryParams" data-route-prefix="{{ Route::getCurrentRoute()->getPrefix() }}">

                            <thead>
                                <tr>
                                    <th data-checkbox="true"></th>
                                    <th data-field="id"
                                        data-visible="{{ in_array('id', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true">ID</th>
                                    <th data-field="nome"
                                        data-visible="{{ in_array('nome', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true">Nome</th>
                                    <th data-field="clientes_count"
                                        data-visible="{{ in_array('clientes_count', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true">Número de Clientes</th>
                                    <th data-field="created_at"
                                        data-visible="{{ in_array('created_at', $visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true">Criado em</th>
                                    <th data-field="updated_at"
                                        data-visible="{{ in_array('updated_at', $visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true">Atualizado em</th>
                                    <th data-field="actions"
                                        data-visible="{{ in_array('actions', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}">
                                        Ações</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para adicionar novo servidor -->
    <div class="modal fade" id="addServidor" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-simple modal-add-servidor">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h3 class="mb-2">Adicionar Novo Servidor</h3>
                        <p class="text-muted">Preencha os detalhes do novo servidor.</p>
                    </div>
                    <form id="addServidorForm" class="row g-3" action="{{ route('servidores.store') }}" method="POST">
                        @csrf
                        <div class="col-12">
                            <label class="form-label" for="addServidorNome">Nome</label>
                            <input type="text" id="addServidorNome" name="nome" class="form-control"
                                placeholder="Nome do Servidor" required />
                        </div>
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Adicionar</button>
                            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal"
                                aria-label="Close">Cancelar</button>
                        </div>
                    </form>
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

    <!-- Modal de Confirmação para Exclusão Múltipla -->
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

    <!-- Modal de Confirmação para Exclusão Individual -->
    @foreach ($servidores as $servidor)
        <div class="modal fade" id="deleteServidor{{ $servidor->id }}" tabindex="-1" aria-labelledby="deleteServidor{{ $servidor->id }}Label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteServidor{{ $servidor->id }}Label">Excluir Servidor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Tem certeza que deseja excluir o servidor <strong>{{ $servidor->nome }}</strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form action="{{ route('servidores.destroy', $servidor->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Modal de Edição -->
    @foreach ($servidores as $servidor)
        <div class="modal fade" id="editServidor{{ $servidor->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-simple modal-edit-servidor">
                <div class="modal-content p-3 p-md-5">
                    <div class="modal-body">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Editar Servidor</h3>
                            <p class="text-muted">Atualize os detalhes do servidor.</p>
                        </div>
                        <form id="editServidorForm{{ $servidor->id }}" class="row g-3"
                            action="{{ route('servidores.update', $servidor->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="col-12">
                                <label class="form-label" for="editServidorNome{{ $servidor->id }}">Nome</label>
                                <input type="text" id="editServidorNome{{ $servidor->id }}" name="nome"
                                    class="form-control" value="{{ $servidor->nome }}" required />
                            </div>
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1">Salvar</button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal"
                                    aria-label="Close">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

@endsection