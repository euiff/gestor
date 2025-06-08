<div class="modal fade" id="editClient{{ $cliente->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-simple modal-edit-client">
        <div class="modal-content p-3 p-md-5">
            <div class="modal-body">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="text-center mb-4">
                    <h3 class="mb-2">Editar Cliente</h3>
                    <p class="text-muted">Atualize os detalhes do cliente.</p>
                </div>
                <form id="editClientForm{{ $cliente->id }}" class="row g-3" action="{{ route('app-ecommerce-customer-update', $cliente->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="col-12">
                        <label class="form-label" for="editClientNome{{ $cliente->id }}">Nome</label>
                        <input type="text" id="editClientNome{{ $cliente->id }}" name="nome" class="form-control" value="{{ $cliente->nome }}" required />
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="editClientPassword{{ $cliente->id }}">Senha</label>
                        <div class="input-group">
                            <input type="password" id="editClientPassword{{ $cliente->id }}" name="password" class="form-control" value="{{ $cliente->password }}" required />
                            <button type="button" class="btn btn-outline-secondary" onclick="generatePassword('editClientPassword{{ $cliente->id }}')">
                                <i class="fas fa-random"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('editClientPassword{{ $cliente->id }}')">
                                <i class="fas fa-eye" id="togglePasswordIcon{{ $cliente->id }}"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="editClientIPTVNome{{ $cliente->id }}">Usuário IPTV</label>
                        <input type="text" id="editClientIPTVNome{{ $cliente->id }}" name="iptv_nome" class="form-control" value="{{ $cliente->iptv_nome }}" />
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="editClientIPTVSenha{{ $cliente->id }}">Senha IPTV</label>
                        <div class="input-group">
                            <input type="text" id="editClientIPTVSenha{{ $cliente->id }}" name="iptv_senha" class="form-control" value="{{ $cliente->iptv_senha }}" />
                            <button type="button" class="btn btn-outline-secondary" onclick="generatePassword('editClientIPTVSenha{{ $cliente->id }}')">
                                <i class="fas fa-random"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="editClientWhatsApp{{ $cliente->id }}">WhatsApp</label>
                        <input type="text" id="editClientWhatsApp{{ $cliente->id }}" name="whatsapp" class="form-control" value="{{ $cliente->whatsapp }}" required />
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="editClientVencimento{{ $cliente->id }}">Vencimento</label>
                        <input type="date" id="editClientVencimento{{ $cliente->id }}" name="vencimento" class="form-control" value="{{ $cliente->vencimento }}" required />
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="editClientServidor{{ $cliente->id }}">Servidor</label>
                        <select id="editClientServidor{{ $cliente->id }}" name="servidor_id" class="form-select" required>
                            @foreach ($servidores as $servidor)
                                <option value="{{ $servidor->id }}" {{ $cliente->servidor_id == $servidor->id ? 'selected' : '' }}>
                                    {{ $servidor->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="editClientMac{{ $cliente->id }}">MAC</label>
                        <input type="text" id="editClientMac{{ $cliente->id }}" name="mac" class="form-control" value="{{ $cliente->mac }}" />
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="editClientNotificacoes{{ $cliente->id }}">Notificações</label>
                        <select id="editClientNotificacoes{{ $cliente->id }}" name="notificacoes" class="form-select" required>
                            <option value="1" {{ $cliente->notificacoes ? 'selected' : '' }}>Sim</option>
                            <option value="0" {{ !$cliente->notificacoes ? 'selected' : '' }}>Não</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="editClientPlano{{ $cliente->id }}">Plano</label>
                        <select id="editClientPlano{{ $cliente->id }}" name="plano_id" class="form-select" required>
                            @foreach ($planos as $plano)
                                <option value="{{ $plano->id }}" {{ $cliente->plano_id == $plano->id ? 'selected' : '' }}>
                                    {{ $plano->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="editClientNumeroDeTelas{{ $cliente->id }}">Número de Telas</label>
                        <input type="number" id="editClientNumeroDeTelas{{ $cliente->id }}" name="numero_de_telas" class="form-control" value="{{ $cliente->numero_de_telas }}" required />
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="editClientNotas{{ $cliente->id }}">Notas</label>
                        <textarea id="editClientNotas{{ $cliente->id }}" name="notas" class="form-control">{{ $cliente->notas }}</textarea>
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