@extends('layouts/layoutMaster')

@section('title', 'Créditos')

@php
    $visibleColumns = getUserPreferences('creditos');
    $type = 'creditos';
@endphp

@section('page-script')
<script>
    var loadDataUrl = '{{ route('revenda.list') }}';
    var destroyMultipleUrl = '{{ route('revenda.destroyMultiple') }}';
    var label_update = '{{ __('messages.update') }}';
    var label_delete = '{{ __('messages.delete') }}';
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('assets/js/pages/revenda_planos.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
      // Função para calcular o total com base na quantidade de créditos e preço por crédito
      function calcularTotal(creditos, preco) {
          return (creditos * preco).toFixed(2);
      }

      // Função para calcular o preço por crédito com base na quantidade de créditos e total
      function calcularPreco(creditos, total) {
          return (total / creditos).toFixed(2);
      }

      // Adicionar eventos de input nos campos de edição
      const rendasCreditos = @json($rendas_creditos);
      rendasCreditos.forEach(plano => {
          const editCreditos = document.getElementById(`editCreditoCreditos${plano.id}`);
          const editPreco = document.getElementById(`editCreditoPreco${plano.id}`);
          const editTotal = document.getElementById(`editCreditoTotal${plano.id}`);

          if (editCreditos && editPreco && editTotal) {
              editCreditos.addEventListener('input', function () {
                  editTotal.value = calcularTotal(editCreditos.value, editPreco.value);
              });

              editPreco.addEventListener('input', function () {
                  editTotal.value = calcularTotal(editCreditos.value, editPreco.value);
              });

              editTotal.addEventListener('input', function () {
                  editPreco.value = calcularPreco(editCreditos.value, editTotal.value);
              });
          }
      });

      // Adicionar eventos de input nos campos de adição
      const addCreditos = document.getElementById('addCreditoCreditos');
      const addPreco = document.getElementById('addCreditoPreco');
      const addTotal = document.getElementById('addCreditoTotal');

      if (addCreditos && addPreco && addTotal) {
          addCreditos.addEventListener('input', function () {
              addTotal.value = calcularTotal(addCreditos.value, addPreco.value);
          });

          addPreco.addEventListener('input', function () {
              addTotal.value = calcularTotal(addCreditos.value, addPreco.value);
          });

          addTotal.addEventListener('input', function () {
              addPreco.value = calcularPreco(addCreditos.value, addTotal.value);
          });
      }
  });
</script>
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
            <span class="text-muted fw-light">{{ config('variables.templateName', 'TemplateName') }} / </span> Créditos
        </h4>

        <!-- Botão para abrir o modal de adicionar crédito -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addCredito">   <i class='bx bx-plus'></i>Adicionar Crédito</button>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <input type="hidden" id="data_type" value="creditos">
                    <input type="hidden" id="save_column_visibility" name="visible_columns">
                    <div class="fixed-table-toolbar">
                    </div>
                    <table id="table" data-toggle="table" data-loading-template="loadingTemplate"
                        data-url="{{ route('revenda.list') }}" data-icons-prefix="bx" data-icons="icons"
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
                                <th data-field="creditos" data-visible="{{ in_array('creditos', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Créditos</th>
                                <th data-field="preco" data-visible="{{ in_array('preco', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Preço por Crédito</th>
                                <th data-field="total" data-visible="{{ in_array('total', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}" data-sortable="true">Total</th>
                                <th data-field="actions" data-visible="{{ in_array('actions', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}">Ações</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal para adicionar novo crédito -->
        <div class="modal fade" id="addCredito" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-simple modal-add-credito">
                <div class="modal-content p-3 p-md-5">
                    <div class="modal-body">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Adicionar Novo Crédito</h3>
                            <p class="text-muted">Preencha os detalhes do novo crédito.</p>
                        </div>
                        <form id="addCreditoForm" class="row g-3" action="{{ route('revenda.store') }}" method="POST">
                            @csrf
                            <div class="col-12">
                                <label class="form-label" for="addCreditoNome">Nome</label>
                                <input type="text" id="addCreditoNome" name="nome" class="form-control" placeholder="Nome do Crédito" required />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="addCreditoCreditos">Créditos</label>
                                <input type="number" id="addCreditoCreditos" name="creditos" class="form-control" placeholder="Quantidade de Créditos" required />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="addCreditoPreco">Preço por Crédito</label>
                                <input type="number" step="0.01" id="addCreditoPreco" name="preco" class="form-control" placeholder="Preço por Crédito" required />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="addCreditoTotal">Total</label>
                                <input type="number" step="0.01" id="addCreditoTotal" name="total" class="form-control" placeholder="Total" required />
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
    </div>
@endsection