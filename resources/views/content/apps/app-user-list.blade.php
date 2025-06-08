@extends('layouts/layoutMaster')

@section('title', 'Usuários')

@php
    $visibleColumns = getUserPreferences('usuarios');
    $type = 'usuarios';
@endphp

@section('page-script')
<script>
    var label_update = '{{ __('messages.update') }}';
    var label_delete = '{{ __('messages.delete') }}';
    var destroyMultipleUrl = '{{ route('users.destroy_multiple') }}';
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('assets/js/pages/users.js') }}"></script>
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
                            Usuários
                        </li>
                    </ol>
                </nav>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class='bx bx-plus'></i> Adicionar Usuário
                </button>
            </div>
        </div>
                 <div class="card">
              <div class="card-body">
                  <div class="table-responsive text-nowrap">
                      <input type="hidden" id="data_type" value="usuarios">
                      <input type="hidden" id="save_column_visibility" name="visible_columns">
                      <div class="fixed-table-toolbar">
                      </div>
                      <table id="table" data-toggle="table" data-loading-template="loadingTemplate"
                          data-url="{{ route('app-user-list-data') }}" data-icons-prefix="bx" data-icons="icons"
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
                                  <th data-field="name"
                                      data-visible="{{ in_array('name', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                      data-sortable="true">Nome</th>
                                  <th data-field="user_id"
                                      data-visible="{{ in_array('user_id', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                      data-sortable="true">Dono</th>
                                  <th data-field="whatsapp"
                                      data-visible="{{ in_array('whatsapp', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                      data-sortable="true">WhatsApp</th>
                                  <th data-field="trial_ends_at"
                                      data-visible="{{ in_array('trial_ends_at', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                      data-sortable="true">Vencimento</th>
                                  <th data-field="role"
                                      data-visible="{{ in_array('role', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                      data-sortable="true">Permissões</th>
                                  <th data-field="status"
                                      data-visible="{{ in_array('status', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                      data-sortable="true">Status</th>
                                  <th data-field="plano"
                                      data-visible="{{ in_array('plano', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                      data-sortable="true">Plano</th>
                                  <th data-field="limite"
                                      data-visible="{{ in_array('limite', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                      data-sortable="true">Limite</th>
                                  <th data-field="creditos"
                                      data-visible="{{ in_array('creditos', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                      data-sortable="true">Créditos</th>
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

    <!-- Modal para adicionar novo usuário -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-simple modal-add-user">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h3 class="mb-2">Adicionar Novo Usuário</h3>
                        <p class="text-muted">Preencha os detalhes do novo usuário.</p>
                    </div>
                    <form id="addUserForm" class="row g-3" action="{{ route('users.store') }}" method="POST">
                        @csrf
                        <div class="col-12">
                            <label class="form-label" for="addUserName">Nome</label>
                            <input type="text" id="addUserName" name="name" class="form-control"
                                placeholder="Nome do Usuário" required />
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="addUserWhatsapp">WhatsApp</label>
                            <input type="text" id="addUserWhatsapp" name="whatsapp" class="form-control" required />
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="addUserRole">Role</label>
                            <input type="text" id="addUserRole" name="role_id" class="form-control" required />
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="addUserStatus">Status</label>
                            <input type="text" id="addUserStatus" name="status" class="form-control" required />
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="addUserPlan">Plano</label>
                            <input type="text" id="addUserPlan" name="plano_id" class="form-control" required />
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
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                      <button type="submit" class="btn btn-danger" id="confirmDeleteSelections">Sim</button>
                  </div>
              </div>
          </div>
      </div>
  </div>

    <!-- Modal de Confirmação para Exclusão Individual -->
    @foreach ($users as $user)
        <div class="modal fade" id="deleteUser{{ $user->id }}" tabindex="-1" aria-labelledby="deleteUser{{ $user->id }}Label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteUser{{ $user->id }}Label">Excluir Usuário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Tem certeza que deseja excluir o usuário <strong>{{ $user->name }}</strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;">
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
    @foreach ($users as $user)
        <div class="modal fade" id="editUser{{ $user->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-simple modal-edit-user">
                <div class="modal-content p-3 p-md-5">
                    <div class="modal-body">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Editar Usuário</h3>
                            <p class="text-muted">Atualize os detalhes do usuário.</p>
                        </div>
                        <form id="editUserForm{{ $user->id }}" class="row g-3"
                            action="{{ route('users.update', $user->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="col-12">
                                <label class="form-label" for="editUserName{{ $user->id }}">Nome</label>
                                <input type="text" id="editUserName{{ $user->id }}" name="name"
                                    class="form-control" value="{{ $user->name }}" required />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="editUserWhatsapp{{ $user->id }}">WhatsApp</label>
                                <input type="text" id="editUserWhatsapp{{ $user->id }}" name="whatsapp"
                                    class="form-control" value="{{ $user->whatsapp }}" required />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="editUserRole{{ $user->id }}">Role</label>
                                <select id="editUserRole{{ $user->id }}" name="role_id" class="form-select">
                                    <option value="1" {{ $user->role_id == 1 ? 'selected' : '' }}>Admin</option>
                                    <option value="2" {{ $user->role_id == 2 ? 'selected' : '' }}>Master</option>
                                    <option value="3" {{ $user->role_id == 3 ? 'selected' : '' }}>Cliente</option>
                                    <option value="4" {{ $user->role_id == 4 ? 'selected' : '' }}>Revendedor</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="editUserStatus{{ $user->id }}">Status</label>
                                <select id="editUserStatus{{ $user->id }}" name="status" class="form-control" required>
                                    <option value="ativo" {{ $user->status == 'ativo' ? 'selected' : '' }}>Ativo</option>
                                    <option value="desativado" {{ $user->status == 'desativado' ? 'selected' : '' }}>Desativado</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="editUserPlan{{ $user->id }}">Plano</label>
                                <select id="editUserPlan{{ $user->id }}" name="plano_id" class="form-control" required
                                    onchange="updateLimite({{ $user->id }})">
                                    @foreach($planos_revenda as $plano)
                                        <option value="{{ $plano->id }}" data-limite="{{ $plano->limite }}" {{ $user->plano_id == $plano->id ? 'selected' : '' }}>{{ $plano->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="editUserLimite{{ $user->id }}">Limite</label>
                                <input type="number" id="editUserLimite{{ $user->id }}" name="limite" class="form-control"
                                    value="{{ $user->limite }}" required />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="editUserCreditos{{ $user->id }}">Créditos</label>
                                <input type="number" id="editUserCreditos{{ $user->id }}" name="creditos" class="form-control"
                                    value="{{ $user->creditos }}" required />
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

    <!-- Modal de Renovação -->
    @foreach ($users as $user)
        <div class="modal fade" id="renewUserModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-simple modal-renew-user">
                <div class="modal-content p-3 p-md-5">
                    <div class="modal-body">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Renovar Usuário</h3>
                            <p class="text-muted">Atualize os detalhes da renovação do usuário.</p>
                        </div>
                        <form id="renewUserForm{{ $user->id }}" class="row g-3" action="{{ route('users.renew', $user->id) }}"
                            method="POST">
                            @csrf
                            <div class="col-12">
                                <label class="form-label" for="renewUserStatus{{ $user->id }}">Status</label>
                                <select id="renewUserStatus{{ $user->id }}" name="status" class="form-control" required>
                                    <option value="ativo" {{ $user->status == 'ativo' ? 'selected' : '' }}>Ativo</option>
                                    <option value="desativado" {{ $user->status == 'desativado' ? 'selected' : '' }}>Desativado</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="renewUserTrialEndsAt{{ $user->id }}">Data de Término do Teste</label>
                                <input type="date" id="renewUserTrialEndsAt{{ $user->id }}" name="trial_ends_at"
                                    class="form-control" value="{{ $user->trial_ends_at ?? \Carbon\Carbon::now()->format('Y-m-d') }}"
                                    required />
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