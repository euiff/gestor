<?php $__env->startSection('title', 'Conexões do WhatsApp'); ?>

<?php $__env->startSection('vendor-style'); ?>
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/animate-css/animate.css')); ?>">
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script src="<?php echo e(asset('assets/js/ui-modals.js')); ?>"></script>
<script>
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
        // Função para aplicar a máscara
    function mask(o, f) {
        v_obj = o;
        v_fun = f;
        setTimeout(function() { execmask(); }, 1);
    }

    function execmask() {
        v_obj.value = v_fun(v_obj.value);
    }

    // Função de máscara para telefone
    function masktel(input) {
    let v = input.value.replace(/\D/g, ""); // Remove tudo que não é dígito
    v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); // Coloca parênteses em volta dos dois primeiros dígitos
    v = v.replace(/(\d)(\d{4})$/, "$1-$2"); // Coloca hífen antes dos últimos 4 dígitos
    input.value = v; // Atualiza o valor do campo com a máscara aplicada
}

    // Validação do campo de telefone
    document.getElementById('phone').addEventListener('blur', function() {
        const phoneInput = this.value.replace(/\D/g, ''); // Remove todos os caracteres não numéricos
        const phoneError = document.getElementById('phoneError');

        if (phoneInput.length < 11) { // Verifica se o número tem 11 dígitos
            phoneError.style.display = 'block';
        } else {
            phoneError.style.display = 'none';
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">WhatsApp /</span> Conexões
</h4>

<div class="container">
 <!-- Exibir mensagens de sucesso ou erro -->
 <?php if(session('success')): ?>
        <div class="alert alert-success">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="alert alert-danger">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>
    <div id="alert-container"></div>
    <?php
        $user_id = Auth::user()->id;
        $conexao = $conexoes->where('user_id', $user_id)->first();
    ?>
    <!-- Formulário para criar nova conexão -->
    <?php if(!$conexao || $conexao->conn != 1): ?>
    <!-- Formulário para criar nova conexão -->
    <form id="connectionForm" action="<?php echo e(route('create-connection')); ?>" method="GET" onsubmit="return validateForm()">
        <div class="mb-3">
            <label for="phone" class="form-label">Número do WhatsApp</label>
            <input type="text" class="form-control" id="phone" name="phone" placeholder="Digite o número do WhatsApp" maxlength="15" oninput="masktel(this)">
            <div id="phoneError" class="text-danger mt-2" style="display: none;">O número do WhatsApp é obrigatório e deve ter 11 dígitos.</div>
        </div>
        <button type="submit" class="btn btn-primary mb-4">Criar Conexão</button>
    </form>
<?php endif; ?>

    <!-- <h2 class="mt-5">Conexões Existentes</h2> -->
    <div class="card">
      <!-- <h5 class="card-header">Conexões do WhatsApp</h5> -->
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>ID do Usuário</th>
              <th>Número do WhatsApp</th>
              <th>Status</th>
              <!-- <th>Data de Cadastro</th> -->
              <th>Ações</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            <?php $__currentLoopData = $conexoes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conexao): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
              <td><?php echo e($conexao->id); ?></td>
              <td><?php echo e($conexao->user_id); ?></td>
              <td><?php echo e($conexao->whatsapp); ?></td>
              <td><span class="badge bg-label-<?php echo e($conexao->conn ? 'primary' : 'secondary'); ?>"><?php echo e($conexao->conn ? 'Conectado' : 'Desconectado'); ?></span></td>
              <!-- <td><?php echo e($conexao->data_cadastro); ?></td> -->
              <td>
                <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                        <i class="ti ti-dots-vertical"></i>
                    </button>
                    <div class="dropdown-menu">
                        <button type="button" class="dropdown-item delete" onclick="confirmDelete(<?php echo e($conexao->id); ?>)">
                            <i class="ti ti-trash me-1"></i> Apagar
                        </button>
                    </div>
                </div>
              </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger">Apagar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
    $user_id = Auth::user()->id;
    $conexao = $conexoes->where('user_id', $user_id)->first();
?>
<?php if($conexao && $conexao->conn == 0): ?>
<!-- Modal Authentication App -->
<div class="modal fade" id="startWa" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered modal-add-new-role">
    <div class="modal-content p-3 p-md-5">
      <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-body">
        <div class="text-center mb-4">
          <h3 class="role-title mb-2">Autenticação do WhatsApp</h3>
          <p class="text-muted">Conecte seu WhatsApp ao sistema</p>
        </div>
        <div class="text-center">
          <h5 class="mb-2 pt-1 text-break">Instruções</h5>
          <p class="mb-4">
            1. Abra o WhatsApp no seu celular.<br>
            2. Vá em "Mais opções" ou "Configurações" e selecione "Aparelhos Conectados".<br>
            3. Toque em "Conectar Aparelho".<br>
            4. Aponte seu celular para o QR Code abaixo.
          </p>
          <img src="<?php echo e($conexao->qrcode); ?>" alt="QR Code" style="filter:grayscale(1)" width="300">
          <div class="card-text" align="center">Atualizando em <span id="contador">15</span> segundos</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar o modal
    var myModal = new bootstrap.Modal(document.getElementById('startWa'));
    myModal.show();

    var contador = document.getElementById('contador');
    var tempo = 15;
    var intervalo;

    function atualizarQRCode() {
        fetch('<?php echo e(route('update-connection')); ?>?phone=<?php echo e($conexao->whatsapp); ?>', {
            method: 'GET'
        })
        .then(response => {
            // Verificar o tipo de conteúdo da resposta
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                return response.text().then(text => {
                    throw new Error('Resposta não é JSON: ' + text);
                });
            }
        })
        .then(data => {
            if (data.qrcode) {
                document.querySelector('#startWa img').src = data.qrcode;
                reiniciarContador();
            } else if (data.success) {
                // Exibir a mensagem de sucesso
                mostrarAlertaSucesso(data.success);
                // Recarregar a página com mensagem de sucesso
                window.location.reload();
            } else {
                console.error('Erro ao atualizar o QR Code:', data.error);
            }
        })
        .catch(error => console.error('Erro na requisição:', error));
    }

    function iniciarContador() {
        intervalo = setInterval(function() {
            tempo--;
            contador.textContent = tempo;
            if (tempo <= 0) {
                clearInterval(intervalo);
                atualizarQRCode();
            }
        }, 1000);
    }

    function reiniciarContador() {
        clearInterval(intervalo);
        tempo = 15;
        contador.textContent = tempo;
        iniciarContador();
    }

    function mostrarAlertaSucesso(mensagem) {
        var alertContainer = document.getElementById('alert-container');
        var alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        alertContainer.appendChild(alertDiv);
    }

    // Verificar se já estamos conectados
    var connStatus = parseInt('<?php echo e($conexao->conn); ?>', 10); // Garantir que é um número
    // console.log('Status da Conexão:', connStatus);

    if (connStatus === 1) {
        // Recarregar a página
        window.location.reload();
    } else {
        iniciarContador();
    }
});
</script>
<?php endif; ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/gestor.veetv.fun/resources/views/content/apps/app-whatsapp.blade.php ENDPATH**/ ?>