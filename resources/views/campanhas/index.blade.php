@extends('layouts/layoutMaster')

@section('title', 'Campanhas')

@php
    $visibleColumns = getUserPreferences('campanhas');
    $type = 'campanhas';
@endphp

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}">
<style>
    /* Garante que os ícones Boxicons sejam renderizados corretamente */
    .bx {
        font-family: 'boxicons' !important;
        font-weight: normal;
        font-style: normal;
        font-size: 1.2rem;
        line-height: 1;
        letter-spacing: normal;
        text-transform: none;
        display: inline-block;
        white-space: nowrap;
        word-wrap: normal;
        direction: ltr;
        -webkit-font-feature-settings: "liga";
        -webkit-font-smoothing: antialiased;
    }
    
    /* Estilo específico para os botões da toolbar */
    .fixed-table-toolbar .btn i.bx {
        vertical-align: middle;
        margin-right: 0.25rem;
    }
    
    .select2.select2-container.select2-container--default:not( #global-search + .select2-container ) {
    display: block !important;
    width: 100% !important;
    min-height: calc(2.25rem + 2px) !important;
    padding: 0.2rem 0.75rem !important;
    font-size: 1rem !important;
    font-weight: 400 !important;
    line-height: 1.5 !important;
    color: #495057 !important;
    background-color: #2f3349 !important;
    background-clip: padding-box !important;
    border: var(--bs-border-width) solid #434968;
    border-radius: 0.25rem !important;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #7367f0;
    border-radius: 4px;
    box-sizing: border-box;
    display: inline-block;
    margin-left: 5px;
    margin-top: 5px;
    padding: 0;
    padding-left: 20px;
    position: relative;
    color: white;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: bottom;
    white-space: nowrap;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    background-color: transparent;
    border: none;
    border-right: 1px solid #aaa;
    border-top-left-radius: 4px;
    border-bottom-left-radius: 4px;
    color: white;
    cursor: pointer;
    font-size: 1em;
    font-weight: bold;
    padding: 0 4px;
    position: absolute;
    left: 0;
    top: 0;
}
</style>
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
@endsection

@section('page-script')
<script>

document.addEventListener('DOMContentLoaded', function() {
    // Seleciona o modal pelo ID
    const addCampanhaModal = document.getElementById('addCampanha');
    
    // Adiciona um listener para quando o modal é mostrado
    addCampanhaModal.addEventListener('shown.bs.modal', function() {
        const origemManual = document.getElementById('origemManual');
        const origemVencidos = document.getElementById('origemVencidos');
        const origemAtivos = document.getElementById('origemAtivos');
        const clientesContainer = document.getElementById('clientesContainer');
        
        function toggleClientesContainer() {
            if (origemManual.checked) {
                clientesContainer.classList.remove('d-none');
            } else {
                clientesContainer.classList.add('d-none');
            }
        }
        
        // Remove event listeners antigos para evitar duplicação
        [origemManual, origemVencidos, origemAtivos].forEach(radio => {
            radio.removeEventListener('change', toggleClientesContainer);
        });
        
        // Adiciona os novos event listeners
        [origemManual, origemVencidos, origemAtivos].forEach(radio => {
            radio.addEventListener('change', toggleClientesContainer);
        });
        
        // Inicializa o estado
        toggleClientesContainer();
    });
});

$(document).ready(function() {
    // Configuração mínima necessária - a tabela já é inicializada pelos atributos data-*
    $('#table').bootstrapTable('refreshOptions', {
        queryParams: function(params) {
            return {
                limit: params.limit,
                offset: params.offset,
                search: params.search,
                sort: params.sort,
                order: params.order
            };
        },
        responseHandler: function(res) {
            return {
                rows: res.rows,
                total: res.total
            };
        },
        onLoadError: function(status, jqXHR) {
            console.error('Erro ao carregar dados:', jqXHR.responseText);
            alert('Erro ao carregar dados. Verifique o console para detalhes.');
        }
    });

    // Restante do seu código JavaScript...
});

    function statusFormatter(value, row) {
        if (row.enviar_diariamente) {
            return '<span class="badge bg-info">Recorrente</span>';
        }
        
        if (!row.data || row.data === 'N/A') {
            return '<span class="badge bg-warning">Pendente</span>';
        }
        
        try {
            const [day, month, year] = row.data.split('/');
            const campanhaDate = new Date(`${year}-${month}-${day}T${row.horario}:00`);
            const now = new Date();
            
            return campanhaDate < now 
                ? '<span class="badge bg-secondary">Enviada</span>'
                : '<span class="badge bg-primary">Agendada</span>';
        } catch (e) {
            console.error("Erro ao formatar status:", e);
            return '<span class="badge bg-warning">Pendente</span>';
        }
    }
    
    var loadDataUrl = '{{ route('campanhas.data') }}';
    function getDestroyUrl(id) {
    return '{{ route("campanhas.destroy", ":id") }}'.replace(':id', id);
}
    var label_update = '{{ __('messages.update') }}';
    var label_delete = '{{ __('messages.delete') }}';
    
    $(document).ready(function() {
        // Alternar entre origem de contatos (clientes/servidores)
        $('input[name="origem_contatos"]').change(function() {
            if ($(this).val() === 'clientes') {
                $('#clientesContainer').removeClass('d-none');
                $('#servidoresContainer').addClass('d-none');
            } else {
                $('#clientesContainer').addClass('d-none');
                $('#servidoresContainer').removeClass('d-none');
            }
        });

        // Inicializar select2
        $('.select2').select2({
            dropdownParent: $('#addCampanha')
        });

        // Habilitar/desabilitar campo de data quando é recorrente
        $('#enviarDiariamente').change(function() {
            if ($(this).is(':checked')) {
                $('#addCampanhaData').val('').prop('disabled', true);
            } else {
                $('#addCampanhaData').prop('disabled', false);
            }
        });
    });

</script>

<script>
$(document).ready(function() {
    // Limpa classes duplicadas após a tabela carregar
    $('#table').on('post-body.bs.table', function() {
        $('.fixed-table-toolbar .btn i').each(function() {
            const classes = $(this).attr('class').split(' ');
            const uniqueClasses = [];
            
            // Remove classes duplicadas
            classes.forEach(cls => {
                if (!uniqueClasses.includes(cls)) {
                    uniqueClasses.push(cls);
                }
            });
            
            $(this).attr('class', uniqueClasses.join(' '));
        });
    });
});
</script>
@endsection

@section('content')

<style>
    .fixed-table-toolbar .btn i.bx {
        vertical-align: middle;
        margin-right: 0.25rem;
    }
    
    .form-select select2 {
        background:red;
    }
    
    .select2.select2-container.select2-container--default:not( #global-search + .select2-container ) {
    display: block !important;
    width: 100% !important;
    min-height: calc(2.25rem + 2px) !important;
    padding: 0.2rem 0.75rem !important;
    font-size: 1rem !important;
    font-weight: 400 !important;
    line-height: 1.5 !important;
    color: #495057 !important;
    background-color: #2f3349 !important;
    background-clip: padding-box !important;
    border: var(--bs-border-width) solid #434968;
    border-radius: 0.25rem !important;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #7367f0;
    border-radius: 4px;
    box-sizing: border-box;
    display: inline-block;
    margin-left: 5px;
    margin-top: 5px;
    padding: 0;
    padding-left: 20px;
    position: relative;
    color: white;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: bottom;
    white-space: nowrap;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    background-color: transparent;
    border: none;
    border-right: 1px solid #aaa;
    border-top-left-radius: 4px;
    border-bottom-left-radius: 4px;
    color: white;
    cursor: pointer;
    font-size: 1em;
    font-weight: bold;
    padding: 0 4px;
    position: absolute;
    left: 0;
    top: 0;
}
</style>
<div class="container-fluid">

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

    <h4 class="py-3 mb-2">
        <span class="text-muted fw-light">{{ config('variables.templateName') }} / </span> Campanhas
    </h4>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-menu-theme">
                <div class="card-body">
                    <h5 class="card-title">Clientes Vencidos</h5>
                    <p class="card-text display-4">{{ $clientesVencidos->count() }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-menu-theme">
                <div class="card-body">
                    <h5 class="card-title">Vencem Hoje</h5>
                    <p class="card-text display-4">{{ $clientesVencemHoje->count() }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-menu-theme">
                <div class="card-body">
                    <h5 class="card-title">Clientes Ativos</h5>
                    <p class="card-text display-4">{{ $clientesAtivos->count() }}</p>
                </div>
            </div>
        </div>
    </div>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addCampanha">
            <i class='bx bx-plus'></i> Nova Campanha
        </button>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <input type="hidden" id="data_type" value="campanhas">
                <input type="hidden" id="save_column_visibility" name="visible_columns">
                <div class="fixed-table-toolbar">
                </div>
                    <table id="table" 
       data-toggle="table"
       data-url="{{ route('campanhas.exibir') }}"
       data-icons-prefix="bx"
       data-icons='{"refresh":"bx-refresh"}'
       data-method="get"
       data-pagination="true"
       data-search="true"
       data-show-refresh="true"
       data-show-columns="true"
       data-side-pagination="server"
       data-mobile-responsive="true"
       data-sort-name="id"
       data-sort-order="desc">
                    <thead>
                        <tr>
                            <th data-checkbox="true"></th>
                            <th data-sortable="true" data-field="id">ID</th>
                            <th data-field="nome" data-visible="{{ in_array('nome', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Nome</th>
                            <th data-field="horario" data-visible="{{ in_array('horario', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Horário</th>
                            <th data-field="data" data-visible="{{ in_array('data', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Data</th>
                            <th data-field="contatos_count" data-visible="{{ in_array('contatos_count', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Contatos</th>
                            <th data-field="status" data-visible="{{ in_array('status', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-formatter="statusFormatter" data-sortable="true">Status</th>
                            <th data-field="user_name" data-visible="{{ in_array('user_name', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Criado Por</th>
                            <th data-field="actions" data-visible="{{ in_array('actions', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}">Ações</th>
                        </tr>
                    </thead>
                </table>

            </div>
        </div>
    </div>
    <div class="modal fade" id="addCampanha" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-simple modal-add-campanha">
        <div class="modal-content p-3 p-md-5">
            <div class="modal-body">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="text-center mb-4">
                    <h3 class="mb-2">Nova Campanha</h3>
                    <p class="text-muted">Preencha os detalhes da nova campanha.</p>
                </div>
                <form id="addCampanhaForm" class="row g-3" action="{{ route('campanhas.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="col-12">
                        <label class="form-label" for="addCampanhaNome">Nome <span class="text-danger">*</span></label>
                        <input type="text" id="addCampanhaNome" name="nome" class="form-control" placeholder="Nome da Campanha" required />
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="addCampanhaHorario">Horário <span class="text-danger">*</span></label>
                        <input type="time" id="addCampanhaHorario" name="horario" class="form-control" required />
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="addCampanhaData">Data (opcional)</label>
                        <input type="date" id="addCampanhaData" name="data" class="form-control" />
                        <input type="hidden" id="dataHidden" name="data_hidden" value="">
                    </div>
                    
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                        const enviarDiariamente = document.getElementById('enviarDiariamente');
                        const dataField = document.getElementById('addCampanhaData');
                        const dataHidden = document.getElementById('dataHidden');
                    
                        enviarDiariamente.addEventListener('change', function() {
                            if(this.checked) {
                                // Se enviar diariamente, limpa o campo de data visível
                                dataField.value = '';
                                // Mas mantém o valor no campo hidden
                                dataHidden.value = dataField.value;
                            }
                        });
                    
                        // Atualiza o campo hidden quando a data visível muda
                        dataField.addEventListener('change', function() {
                            dataHidden.value = this.value;
                        });
                    });
                    </script>
                    
                    <div class="col-12">
                        <label class="form-label">Origem dos Contatos <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="origem_contatos" id="origemManual" value="manual" checked>
                            <label class="form-check-label" for="origemManual">Seleção Manual</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="origem_contatos" id="origemVencidos" value="vencidos">
                            <label class="form-check-label" for="origemVencidos">Clientes Vencidos</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="origem_contatos" id="origemAtivos" value="ativos">
                            <label class="form-check-label" for="origemAtivos">Clientes Ativos</label>
                        </div>
                    </div>
                    
                    <div class="col-12" id="clientesContainer">
                        <label class="form-label" for="addCampanhaContatos">Selecione os Clientes</label>
                        <select id="addCampanhaContatos" name="contatos[]" class="form-select select2" multiple>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ignorarContatos" name="ignorar_contatos" value="1" checked>
                            <label class="form-check-label" for="ignorarContatos">Ignorar contatos que já receberam mensagem</label>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label" for="addCampanhaMensagem">Mensagem <span class="text-danger">*</span></label>
                        <textarea id="addCampanhaMensagem" name="mensagem" class="form-control" rows="4" required></textarea>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label" for="addCampanhaArquivo">Imagem (opcional)</label>
                        <input type="file" id="addCampanhaArquivo" name="arquivo" class="form-control" />
                        <small class="text-muted">Formatos permitidos: jpg, jpeg e png. (até 2MB)</small>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enviarDiariamente" name="enviar_diariamente" value="1">
                            <label class="form-check-label" for="enviarDiariamente">Enviar mensagem diariamente neste horário</label>
                        </div>
                    </div>
                    
                    <div class="col-12 text-center">
                        <button type="submit" class="btn btn-primary me-sm-3 me-1">Salvar</button>
                        <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
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
</div>
@endsection