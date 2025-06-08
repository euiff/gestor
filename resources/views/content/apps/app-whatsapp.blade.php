@extends('layouts/layoutMaster')

@section('title', 'Conexões do WhatsApp')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/animate-css/animate.css')}}">
@endsection

@section('page-script')
<script src="{{asset('assets/js/ui-modals.js')}}"></script>

<script>
    function prepareConnectionModal(conexaoId) {
        // Armazena o ID da conexão para uso posterior
        document.getElementById('startWa').setAttribute('data-conexao-id', conexaoId);
        
        // Faz a requisição para obter os dados atualizados
        fetch(`/conexoes/${conexaoId}/connect`)
            .then(response => response.json())
            .then(data => {
                if(data.qrcode) {
                    document.querySelector('#startWa img').src = data.qrcode;
                    document.querySelector('#startWa img').style.display = 'block';
                } else {
                    document.querySelector('#startWa img').style.display = 'none';
                }
                
                if(data.formatted_pairing_code) {
                    const pairingContainer = document.querySelector('#startWa .pairing-code-container');
                    pairingContainer.innerHTML = `
                        <p class="mb-2">Ou conecte-se utilizando esse código:</p>
                        <div class="d-flex justify-content-center">
                            <div class="pairing-code bg-light p-3 rounded d-inline-block">
                                <code class="text-dark fs-4 fw-bold" style="letter-spacing: 1.5px;">
                                    ${data.formatted_pairing_code}
                                </code>
                            </div>
                        </div>
                        <p class="mb-2"><br>Código de pareamento</p>
                    `;
                    pairingContainer.style.display = 'block';
                } else {
                    document.querySelector('#startWa .pairing-code-container').style.display = 'none';
                }
                
                // Inicia o contador
                startCountdown();
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Não foi possível obter os dados de conexão');
            });
    }

    function startCountdown() {
        let seconds = 30;
        const countdownElement = document.querySelector('#startWa #contador');
        
        // Limpa qualquer intervalo existente
        if(window.countdownInterval) {
            clearInterval(window.countdownInterval);
        }
        
        window.countdownInterval = setInterval(() => {
            seconds--;
            countdownElement.textContent = seconds;
            
            if(seconds <= 0) {
                clearInterval(window.countdownInterval);
                // Recarrega os dados quando o contador chegar a zero
                const conexaoId = document.getElementById('startWa').getAttribute('data-conexao-id');
                if(conexaoId) {
                    prepareConnectionModal(conexaoId);
                }
            }
        }, 1000);
    }
    
    function masktel(input) {
        let v = input.value.replace(/\D/g, "");
        v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
        v = v.replace(/(\d)(\d{4})$/, "$1-$2");
        input.value = v;
    }

    // Validação do campo de telefone
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('phone');
        if(phoneInput) {
            phoneInput.addEventListener('blur', function() {
                const phoneDigits = this.value.replace(/\D/g, '');
                const phoneError = document.getElementById('phoneError');

                if (phoneDigits.length < 11) {
                    phoneError.style.display = 'block';
                } else {
                    phoneError.style.display = 'none';
                }
            });
        }
    });

    function validateForm() {
        var phoneInput = document.getElementById('phone');
        var phoneError = document.getElementById('phoneError');

        if (phoneInput.value.trim() === '') {
            phoneError.style.display = 'block';
            return false;
        } else {
            phoneError.style.display = 'none';
            return true;
        }
    }

    function confirmDelete(id) {
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        document.getElementById('deleteForm').action = '/delete-connection/' + id;
        deleteModal.show();
    }
</script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">WhatsApp /</span> Conexões
</h4>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    
    <div id="alert-container"></div>
    
    @php
        $user_id = Auth::user()->id;
        $conexao = $conexoes->where('user_id', $user_id)->first();
    @endphp
    
    @if(!$conexao || $conexao->conn != 1)
    <form id="connectionForm" action="{{ route('create-connection') }}" method="GET" onsubmit="return validateForm()">
        <div class="mb-3">
            <label for="phone" class="form-label">Número do WhatsApp</label>
            <input type="text" class="form-control" id="phone" name="phone" placeholder="Digite o número do WhatsApp" maxlength="15" oninput="masktel(this)">
            <div id="phoneError" class="text-danger mt-2" style="display: none;">O número do WhatsApp é obrigatório e deve ter 11 dígitos.</div>
        </div>
        <button type="submit" class="btn btn-primary mb-4">Criar Conexão</button>
    </form>
    @endif

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>ID do Usuário</th>
              <th>Número do WhatsApp</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @foreach($conexoes as $conexao)
            <tr>
              <td>{{ $conexao->id }}</td>
              <td>{{ $conexao->user_id }}</td>
              <td>{{ $conexao->whatsapp }}</td>
              <td><span class="badge bg-label-{{ $conexao->conn ? 'primary' : 'secondary' }}">{{ $conexao->conn ? 'Conectado' : 'Desconectado' }}</span></td>
              <td>
                <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                        <i class="ti ti-dots-vertical"></i>
                    </button>
                    <div class="dropdown-menu">
                        @if(!$conexao->conn)
                        <button type="button" class="dropdown-item" 
                                data-bs-toggle="modal" 
                                data-bs-target="#startWa"
                                onclick="prepareConnectionModal({{ $conexao->id }})">
                            <i class="ti ti-plug-connected me-1"></i> Conectar
                        </button>
                        @endif
                        <button type="button" class="dropdown-item delete" onclick="confirmDelete({{ $conexao->id }})">
                            <i class="ti ti-trash me-1"></i> Apagar
                        </button>
                    </div>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar Exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Tem certeza que deseja apagar essa instância?
      </div>
      <div class="modal-footer">
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger">Apagar</button>
        </form>
      </div>
    </div>
  </div>
</div>
@php
    $user_id = Auth::user()->id;
    $conexao = $conexoes->where('user_id', $user_id)->first();
@endphp

@if($conexao && $conexao->conn == 0)
<!-- Modal Authentication App -->
<div class="modal fade" id="startWa" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content p-3 p-md-5">
      <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-body">
        <div class="text-center mb-4">
          <h3 class="role-title mb-2">Autenticação do WhatsApp</h3>
          <p class="text-muted">Conecte seu WhatsApp ao sistema</p>
        </div>
        
        <div class="d-flex flex-column align-items-center">
          <div class="text-center mb-4" style="max-width: 500px;">
            <h5 class="mb-3">Instruções</h5>
            <ol class="list-unstyled mb-4">
              <li class="mb-2">1. Abra o WhatsApp no seu celular</li>
              <li class="mb-2">2. Vá em "Mais opções" ou "Configurações"</li>
              <li class="mb-2">3. Selecione "Aparelhos Conectados"</li>
              <li>4. Toque em "Conectar Aparelho"</li>
            </ol>
            <div class="d-flex justify-content-center mb-4">
              <img src="" alt="QR Code" 
                   style="filter:grayscale(1); width: 300px; display: none;" 
                   class="img-fluid border rounded">
            </div>
            <div class="pairing-code-container mb-4" style="display: none;">
            </div>
            <div class="mt-3">
              <p class="text-muted mb-1">Aponte seu celular para o QR Code acima</p>
              <div class="card-text">Atualizando em <span id="contador" class="fw-bold">30</span> segundos</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($conexao && $conexao->conn == 0)
    // Mostrar o modal automaticamente
    var startWaModal = new bootstrap.Modal(document.getElementById('startWa'));
    startWaModal.show();
    
    // Carregar os dados da conexão
    prepareConnectionModal({{ $conexao->id }});
    
    // Verificar status da conexão periodicamente
    var checkInterval = setInterval(function() {
        fetch(`/conexoes/check-status/{{ $conexao->id }}`)
            .then(response => response.json())
            .then(data => {
                if(data.status === 'open' || data.conn == 1) {
                    // Fechar o modal
                    startWaModal.hide();
                    // Parar de verificar
                    clearInterval(checkInterval);
                    // Mostrar mensagem de sucesso
                    showSuccessMessage('WhatsApp conectado com sucesso!');
                    // Recarregar a página após 2 segundos
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            })
            .catch(error => console.error('Erro ao verificar status:', error));
    }, 3000); // Verifica a cada 5 segundos
    
    // Função para mostrar mensagem de sucesso
    function showSuccessMessage(message) {
        // Criar elemento de alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Adicionar ao container de alertas
        const alertContainer = document.getElementById('alert-container');
        alertContainer.prepend(alertDiv);
        
        // Remover automaticamente após 5 segundos
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
    @endif
});
</script>
@endif
@endsection