@extends('layouts/layoutMaster')

@section('title', 'Templates')

@php
    $visibleColumns = getUserPreferences('templates');
    $type = 'templates';
@endphp

@section('page-script')
<script>
    var loadDataUrl = '{{ route('templates.list') }}';
    var destroyMultipleUrl = '{{ route('templates.destroy-multiple') }}';
    var label_update = '{{ __('messages.update') }}';
    var label_delete = '{{ __('messages.delete') }}';
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('assets/js/pages/templates.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Função que funciona para TODOS os modais
    function setupTriggers(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const textarea = modal.querySelector('textarea[name="conteudo"]');
        const buttons = modal.querySelectorAll('[data-gatilho]');

        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const trigger = this.getAttribute('data-gatilho');
                const startPos = textarea.selectionStart;
                const endPos = textarea.selectionEnd;
                textarea.value = textarea.value.substring(0, startPos) + trigger + textarea.value.substring(endPos);
                textarea.selectionStart = textarea.selectionEnd = startPos + trigger.length;
                textarea.focus();
            });
        });
    }

    // Configura os gatilhos do modal de criação
    setupTriggers('addTemplate');

    // Configura os gatilhos de cada modal de edição
    @foreach ($templates as $template)
        setupTriggers('editTemplate{{ $template->id }}');
    @endforeach

    // Controle de exibição dos campos de imagem
    function setupImageFieldVisibility() {
        document.querySelectorAll('[id^="tipo_mensagem"]').forEach(select => {
            const containerId = select.id.replace('tipo_mensagem', '');
            const conteudoContainer = document.getElementById(`conteudo_container${containerId}`);
            const imagemContainer = document.getElementById(`imagem_container${containerId}`);

            function updateVisibility() {
                if (select.value === 'texto') {
                    if (conteudoContainer) conteudoContainer.style.display = 'block';
                    if (imagemContainer) imagemContainer.style.display = 'none';
                } else if (select.value === 'texto_com_imagem') {
                    if (conteudoContainer) conteudoContainer.style.display = 'block';
                    if (imagemContainer) imagemContainer.style.display = 'block';
                }
            }

            select.addEventListener('change', updateVisibility);
            updateVisibility(); // Atualiza no carregamento
        });
    }

    setupImageFieldVisibility();
});
</script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">{{ config('variables.templateName', 'TemplateName') }} / </span> Templates
</h4>

@if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addTemplate">Adicionar Template</button>

<div class="card">
    <div class="card-body">
        <div class="table-responsive text-nowrap">
            <input type="hidden" id="data_type" value="templates">
            <input type="hidden" id="save_column_visibility" name="visible_columns">
            <div class="fixed-table-toolbar">
            </div>
            <table id="table" data-toggle="table" data-loading-template="loadingTemplate"
                data-url="{{ route('templates.list') }}" data-icons-prefix="bx" data-icons="icons"
                data-show-refresh="true" data-total-field="total" data-trim-on-search="false"
                data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                data-side-pagination="server" data-show-columns="true" data-pagination="true"
                data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                data-query-params="queryParams" data-route-prefix="{{ Route::getCurrentRoute()->getPrefix() }}">
                <thead>
                    <tr>
                        <th data-checkbox="true"></th>
                        <th data-field="id" data-visible="{{ in_array('id', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">ID</th>
                        <th data-field="nome" data-visible="{{ in_array('nome', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Nome</th>
                        <th data-field="user_name" data-visible="{{ in_array('user_name', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Dono</th>
                        <th data-field="finalidade" data-visible="{{ in_array('finalidade', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Finalidade</th>
                        <th data-field="conteudo" data-visible="{{ in_array('conteudo', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Conteúdo</th>
                        <th data-field="actions" data-visible="{{ in_array('actions', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Adição -->
<div class="modal fade" id="addTemplate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-simple modal-add-template">
        <div class="modal-content p-3 p-md-5">
            <div class="modal-body">
                <h5 class="modal-title">Adicionar Template</h5>
                <form action="{{ route('templates.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="finalidade" class="form-label">Finalidade</label>
                        <select class="form-select" id="finalidade" name="finalidade" required>
                            <option value="cobranca_manual">Cobrança Manual</option>
                            <option value="vencidos">Vencidos</option>
                            <option value="pagamentos">Pagamentos</option>
                            <option value="creditos_aprovados">Compras Creditos</option>
                            <option value="cobranca_3_dias_atras">Cobrança 3 Dias Vencidos</option>
                            <option value="cobranca_5_dias_atras">Cobrança 5 Dias Vencidos</option>
                            <option value="cobranca_hoje">Cobrança Hoje</option>
                            <option value="cobranca_3_dias_futuro">Cobrança 3 Dias Futuro</option>
                            <option value="dados_iptv">Dados IPTV</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tipo_mensagem" class="form-label">Tipo de Mensagem</label>
                        <select class="form-select" id="tipo_mensagem" name="tipo_mensagem" required>
                            <option value="texto">Mensagem de Texto</option>
                            <option value="texto_com_imagem">Mensagem de Texto com Imagem</option>
                        </select>
                    </div>
                    <div class="mb-3" id="conteudo_container">
                        <label for="conteudo" class="form-label">Conteúdo</label>
                        <textarea class="form-control" id="conteudo" name="conteudo" required></textarea>
                    </div>
                    <div class="mb-3" id="imagem_container" style="display: none;">
                        <label for="imagem" class="form-label">Imagem</label>
                        <input type="file" class="form-control" id="imagem" name="imagem">
                        <small class="text-muted">Formatos permitidos: jpeg, png, jpg, gif, svg. Tamanho máximo: 2MB.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </form>
                <h5 class="mt-4">Gatilhos para usar no conteúdo</h5>
                <div id="gatilhos">
                    @include('partials.gatilhos')
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Edição -->
@foreach ($templates as $template)
    <div class="modal fade" id="editTemplate{{ $template->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-simple modal-edit-template">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body">
                    <h5 class="modal-title">Editar Template</h5>
                    <form action="{{ route('templates.update', $template->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="nome{{ $template->id }}" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome{{ $template->id }}" name="nome" value="{{ $template->nome }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="finalidade{{ $template->id }}" class="form-label">Finalidade</label>
                            <select class="form-select" id="finalidade{{ $template->id }}" name="finalidade" required>
                                <option value="cobranca_manual" {{ $template->finalidade == 'cobranca_manual' ? 'selected' : '' }}>Cobrança Manual</option>
                                <option value="vencidos" {{ $template->finalidade == 'vencidos' ? 'selected' : '' }}>Vencidos</option>
                                <option value="pagamentos" {{ $template->finalidade == 'pagamentos' ? 'selected' : '' }}>Pagamentos</option>
                                <option value="creditos_aprovados" {{ $template->finalidade == 'creditos_aprovados' ? 'selected' : '' }}>Compras Creditos</option>
                                <option value="cobranca_3_dias_atras" {{ $template->finalidade == 'cobranca_3_dias_atras' ? 'selected' : '' }}>Cobrança 3 Dias vencidos</option>
                                <option value="cobranca_5_dias_atras" {{ $template->finalidade == 'cobranca_5_dias_atras' ? 'selected' : '' }}>Cobrança 5 Dias vencidos</option>
                                <option value="cobranca_hoje" {{ $template->finalidade == 'cobranca_hoje' ? 'selected' : '' }}>Cobrança Hoje</option>
                                <option value="cobranca_3_dias_futuro" {{ $template->finalidade == 'cobranca_3_dias_futuro' ? 'selected' : '' }}>Cobrança 3 Dias Futuro</option>
                                <option value="dados_iptv" {{ $template->finalidade == 'dados_iptv' ? 'selected' : '' }}>Dados do Clientes IPTV</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="tipo_mensagem{{ $template->id }}" class="form-label">Tipo de Mensagem</label>
                            <select class="form-select" id="tipo_mensagem{{ $template->id }}" name="tipo_mensagem" required>
                                <option value="texto" {{ $template->tipo_mensagem == 'texto' ? 'selected' : '' }}>Texto</option>
                                <option value="texto_com_imagem" {{ $template->tipo_mensagem == 'texto_com_imagem' ? 'selected' : '' }}>Texto com Imagem</option>
                            </select>
                        </div>
                        <div class="mb-3" id="conteudo_container{{ $template->id }}">
                            <label for="conteudo{{ $template->id }}" class="form-label">Conteúdo</label>
                            <textarea class="form-control" id="conteudo{{ $template->id }}" name="conteudo" required>{{ $template->conteudo }}</textarea>
                        </div>

                        @if($template->tipo_mensagem === 'texto_com_imagem')
                            <div class="mb-3" id="imagem_container{{ $template->id }}">
                                <label for="imagem{{ $template->id }}" class="form-label">Imagem</label>
                                <input type="file" class="form-control" id="imagem{{ $template->id }}" name="imagem">
                                @if($template->imagem)
                                    <img src="{{ asset($template->imagem) }}" alt="Imagem do Template" width="100" class="mt-2" style="cursor: pointer;" onclick="openImageModal('{{ asset($template->imagem) }}')">
                                @endif
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </form>
                    <h5 class="mt-4">Gatilhos para usar no conteúdo</h5>
                    <div id="gatilhos">
                        @include('partials.gatilhos')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visualizar Imagem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <img id="modalImage" src="" alt="Imagem" style="width: 100%;">
            </div>
        </div>
    </div>
</div>

<script>
    function openImageModal(imageUrl) {
        document.getElementById('modalImage').src = imageUrl;
        new bootstrap.Modal(document.getElementById('imageModal'), {
            backdrop: 'static',
            keyboard: false
        }).show();
    }
</script>

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

<!-- Modal de Confirmação para Excluir Selecionados -->
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