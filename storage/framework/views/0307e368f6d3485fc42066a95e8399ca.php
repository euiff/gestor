<?php $__env->startSection('title', 'Configurações'); ?>

<?php $__env->startSection('vendor-style'); ?>
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/select2/select2.css')); ?>" />
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
<script src="<?php echo e(asset('assets/vendor/libs/select2/select2.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/libs/cleavejs/cleave.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/libs/cleavejs/cleave-phone.js')); ?>"></script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script src="<?php echo e(asset('assets/js/app-ecommerce-settings.js')); ?>"></script>
<script>
    // Função para aplicar a máscara
    function mask(o, f) {
        v_obj = o;
        v_fun = f;
        setTimeout(function() { execmask(); }, 1);
    }

    function execmask() {
        v_obj.value = v_fun(v_obj.value);
    }

    // Função de máscara para CPF
    function maskCPF(v) {
        v = v.replace(/\D/g, ""); // Remove tudo que não é dígito
        v = v.replace(/(\d{3})(\d)/, "$1.$2"); // Coloca um ponto após os primeiros 3 dígitos
        v = v.replace(/(\d{3})(\d)/, "$1.$2"); // Coloca um ponto após os próximos 3 dígitos
        v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2"); // Coloca um hífen antes dos últimos 2 dígitos
        return v;
    }

    // Função de máscara para CNPJ
    function maskCNPJ(v) {
        v = v.replace(/\D/g, ""); // Remove tudo que não é dígito
        v = v.replace(/(\d{2})(\d)/, "$1.$2"); // Coloca um ponto após os primeiros 2 dígitos
        v = v.replace(/(\d{3})(\d)/, "$1.$2"); // Coloca um ponto após os próximos 3 dígitos
        v = v.replace(/(\d{3})(\d)/, "$1/$2"); // Coloca uma barra após os próximos 3 dígitos
        v = v.replace(/(\d{4})(\d{1,2})$/, "$1-$2"); // Coloca um hífen antes dos últimos 2 dígitos
        return v;
    }

    // Função de máscara para Telefone
    function maskTelefone(v) {
        v = v.replace(/\D/g, ""); // Remove tudo que não é dígito
        v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); // Coloca parênteses em volta dos dois primeiros dígitos
        v = v.replace(/(\d)(\d{4})$/, "$1-$2"); // Coloca hífen antes dos últimos 4 dígitos
        return v;
    }

    // Função para inferir o tipo de chave com base no comprimento do valor
    function inferPixType(value) {
        const length = value.replace(/\D/g, '').length; // Remove caracteres não numéricos e verifica o comprimento

        if (length === 11) return 'cpf'; // CPF tem 11 dígitos
        if (length === 14) return 'cnpj'; // CNPJ tem 14 dígitos
        if (length === 11 || length === 10) return 'telefone'; // Telefone tem 10 ou 11 dígitos
        if (value.includes('@')) return 'email'; // E-mail contém '@'
        if (value.length === 36) return 'aleatoria'; // Chave aleatória tem 36 caracteres

        return 'email'; // Padrão para valores desconhecidos
    }

    // Função para aplicar a máscara da chave PIX
    function applyPixMask(input) {
        const pixTypeSelect = document.getElementById('pix-type-select');
        const pixType = pixTypeSelect.value;

        switch (pixType) {
            case 'cpf':
                input.value = maskCPF(input.value);
                break;
            case 'cnpj':
                input.value = maskCNPJ(input.value);
                break;
            case 'telefone':
                input.value = maskTelefone(input.value);
                break;
            case 'email':
            case 'aleatoria':
                // Não aplicamos máscara para e-mail ou chave aleatória
                break;
        }
    }

    // Função para formatar o valor ao carregar a página
    function formatInitialValue() {
        const pixInput = document.getElementById('ecommerce-settings-pix-manual');
        const pixTypeSelect = document.getElementById('pix-type-select');

        // Inferir o tipo de chave com base no valor salvo
        const pixType = inferPixType(pixInput.value);

        // Selecionar o tipo de chave no <select>
        pixTypeSelect.value = pixType;

        // Aplicar a formatação com base no tipo de chave
        applyPixMask(pixInput);
    }

    // Aplica a formatação ao carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        formatInitialValue(); // Formata o valor inicial do campo PIX
    });
</script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<h4 class="py-3 mb-4">
<span class="text-muted fw-light"><?php echo e(config('variables.templateName', 'TemplateName')); ?> / </span> Configurações
</h4>

<?php if(session('success')): ?>
  <div class="alert alert-success alert-dismissible">
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    <?php echo e(session('success')); ?>

  </div>
<?php endif; ?>

<?php if(session('error')): ?>
  <div class="alert alert-danger alert-dismissible">
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    <?php echo e(session('error')); ?>

  </div>
<?php endif; ?>

<div class="row g-4">

  <?php
  $publicKey = config('mercado_pago.public_key');
  $accessToken = config('mercado_pago.access_token');
  $siteId = config('mercado_pago.site_id');
  ?>

  <!-- Options -->
  <div class="col-12 col-lg-8 pt-4 pt-lg-0" style="width:100%">
    <div class="tab-content p-0">
      <!-- Store Details Tab -->
      <div class="tab-pane fade show active" id="store_details" role="tabpanel">
        <?php if(isset($companyDetails)): ?>
        <form action="<?php echo e(route('configuracoes.update', $companyDetails->id)); ?>" method="POST" enctype="multipart/form-data">
          <?php echo method_field('PUT'); ?>
        <?php else: ?>
        <form action="<?php echo e(route('configuracoes.store')); ?>" method="POST" enctype="multipart/form-data">
        <?php endif; ?>
          <?php echo csrf_field(); ?>
          <div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title m-0">Configurações</h5>
    </div>
    <div class="card-body">
        <!-- Seção 1: Informações Básicas da Empresa -->
        <div class="row mb-4 g-3">
            <div class="col-12">
                <h6 class="mb-3">Informações da Empresa</h6>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-details-name">Nome da Empresa <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="ecommerce-settings-details-name" placeholder="Nome da Empresa" name="company_name" aria-label="Nome da Empresa" value="<?php echo e($companyDetails->company_name ?? ''); ?>" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-details-phone">WhatsApp da Empresa <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="ecommerce-settings-details-phone" placeholder="(99) 99999-9999" name="company_whatsapp" aria-label="WhatsApp da Empresa" value="<?php echo e($companyDetails->company_whatsapp ?? ''); ?>" maxlength="15" required oninput="mask(this, maskTelefone)">
            </div>
            
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="qpanel_username">
                    Usuário Qpanel 
                    <span class="badge bg-label-primary rounded-pill ms-auto" style="bottom: 1rem;margin-bottom: 0.5rem;">Novo</span>
                </label>
                <input type="text" class="form-control" id="qpanel_username" 
                       placeholder="Nome de usuário no Qpanel" 
                       name="qpanel_username" 
                       value="<?php echo e($companyDetails->qpanel_username ?? ''); ?>">
                <small class="text-muted">Função disponível apenas para revendedores Veetv</small>
            </div>
            
        </div>

        <div class="row mb-4 g-3">
            <div class="col-12">
                <h6 class="mb-3">Identidade Visual</h6>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-company-logo-light">Logotipo (Tema Claro) <span class="text-danger">*</span></label>
                <input type="file" class="form-control" id="ecommerce-settings-company-logo-light" name="company_logo_light" aria-label="Logotipo da Empresa (Tema Claro)" <?php if(!isset($companyDetails->company_logo_light)): ?> required <?php endif; ?>>
                <?php if(isset($companyDetails->company_logo_light)): ?>
                <img src="<?php echo e(asset($companyDetails->company_logo_light)); ?>" alt="Company Logo Light" width="100" class="mt-2">
                <?php endif; ?>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-company-logo-dark">Logotipo (Tema Escuro) <span class="text-danger">*</span></label>
                <input type="file" class="form-control" id="ecommerce-settings-company-logo-dark" name="company_logo_dark" aria-label="Logotipo da Empresa (Tema Escuro)" <?php if(!isset($companyDetails->company_logo_dark)): ?> required <?php endif; ?>>
                <?php if(isset($companyDetails->company_logo_dark)): ?>
                <img src="<?php echo e(asset($companyDetails->company_logo_dark)); ?>" alt="Company Logo Dark" width="100" class="mt-2">
                <?php endif; ?>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-favicon">Favicon <span class="text-danger">*</span></label>
                <input type="file" class="form-control" id="ecommerce-settings-favicon" name="favicon" aria-label="Favicon" <?php if(!isset($companyDetails->favicon)): ?> <?php endif; ?>>
                <?php if(isset($companyDetails->favicon)): ?>
                <img src="<?php echo e(asset($companyDetails->favicon)); ?>" alt="Favicon" width="32" class="mt-2">
                <?php endif; ?>
            </div>
        </div>

        <!-- Seção 3: Configurações de Pagamento -->
        <div class="row mb-4 g-3">
            <div class="col-12">
                <h6 class="mb-3">Configurações de Pagamento</h6>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-access-token">Access Token <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="ecommerce-settings-access-token" placeholder="Access Token" name="access_token" aria-label="Access Token" value="<?php echo e($companyDetails->access_token ?? ''); ?>">
            </div>
            <?php if(Auth::check() && Auth::user()->role_id == 1): ?>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-public-key">Public Key</label>
                <input type="text" class="form-control" id="ecommerce-settings-public-key" placeholder="Public Key" name="public_key" aria-label="Public Key" value="<?php echo e($companyDetails->public_key ?? ''); ?>">
            </div>
            <?php endif; ?>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-pix-manual">PIX Manual <span class="text-danger">*</span></label>
                <div class="input-group">
                    <select class="form-select" id="pix-type-select" aria-label="Selecione o tipo de chave PIX" onchange="applyPixMask(document.getElementById('ecommerce-settings-pix-manual'))">
                        <option value="email">E-mail</option>
                        <option value="cpf">CPF</option>
                        <option value="cnpj">CNPJ</option>
                        <option value="telefone">Telefone</option>
                        <option value="aleatoria">Chave Aleatória</option>
                    </select>
                    <input type="text" class="form-control" id="ecommerce-settings-pix-manual" placeholder="Chave PIX" name="pix_manual" aria-label="PIX Manual" value="<?php echo e($companyDetails->pix_manual ?? ''); ?>" oninput="applyPixMask(this)">
                </div>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-notification-url">Notification URL <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="ecommerce-settings-notification-url" placeholder="Notification URL" name="notification_url" aria-label="Notification URL" value="<?php echo e(url('/webhook/mercadopago')); ?>" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-not-gateway">Método de Cobrança</label>
                <div class="form-check form-switch">
                    <input type="hidden" name="not_gateway" value="0">
                    <input class="form-check-input" type="checkbox" id="ecommerce-settings-not-gateway" name="not_gateway" value="1" <?php echo e(isset($companyDetails->not_gateway) && $companyDetails->not_gateway ? 'checked' : ''); ?>>
                    <label class="form-check-label" for="ecommerce-settings-not-gateway">Usar chave PIX manual para cobranças</label>
                </div>
                <small class="form-text text-muted">
                    Marque esta opção para usar a chave PIX manual para processar cobranças manualmente. Desmarque para usar o Mercado Pago para cobranças automáticas.
                </small>
            </div>
        </div>

        <?php if(Auth::check() && Auth::user()->role_id == 1): ?>
        <!-- Seção 4: Configurações Avançadas (Admin) -->
        <div class="row mb-4 g-3">
            <div class="col-12">
                <h6 class="mb-3">Configurações Avançadas</h6>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-referral-balance">Saldo de Indicações</label>
                <input type="text" class="form-control" id="ecommerce-settings-referral-balance" placeholder="Saldo de Indicações" name="referral_balance" aria-label="Saldo de Indicações" value="<?php echo e($companyDetails->referral_balance ?? ''); ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-site-id">Site ID</label>
                <input type="text" class="form-control" id="ecommerce-settings-site-id" placeholder="Site ID" name="site_id" aria-label="Site ID" value="<?php echo e($companyDetails->site_id ?? 'MLB'); ?>">
            </div>
        </div>

        <!-- Seção 5: APIs Externas (Admin) -->
        <div class="row mb-4 g-3">
            <div class="col-12">
                <h6 class="mb-3">Integrações com APIs</h6>
            </div>
            <div class="col-12">
                <label class="form-label mb-0" for="ecommerce-settings-api-session">API ipinfo.io</label>
                <input type="text" class="form-control" id="ecommerce-settings-api-session" placeholder="API Session" name="api_session" aria-label="token" value="<?php echo e($companyDetails->api_session ?? ''); ?>">
                <small class="form-text text-muted">
                    Não tem uma API? <a href="https://ipinfo.io/account/home?service=google&loginState=create" target="_blank">Crie sua conta no ipinfo.io</a>
                </small>
            </div>
            
            <!-- Subseção: Qpanel API -->
            <div class="col-12">
                <h6 class="mb-3 mt-4">Qpanel API</h6>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-qpanel-api-url">Qpanel API URL</label>
                <input type="text" class="form-control" id="ecommerce-settings-qpanel-api-url" 
                       placeholder="<?php echo e(isset($companyDetails->qpanel_api_url) ? 'URL oculta por motivos de segurança' : 'Qpanel API URL'); ?>" 
                       name="qpanel_api_url" 
                       value="<?php echo e(isset($companyDetails->qpanel_api_url) ? '' : ($companyDetails->qpanel_api_url ?? '')); ?>">
                       <small class="text-muted">Deixe em branco para manter a url atual</small>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-qpanel-api-key">Qpanel API Key</label>
                <input type="password" class="form-control" id="ecommerce-settings-qpanel-api-key" 
                       placeholder="<?php echo e(isset($companyDetails->qpanel_api_key) ? 'Chave oculta por motivos de segurança' : 'Qpanel API Key'); ?>" 
                       name="qpanel_api_key" 
                       value="<?php echo e(isset($companyDetails->qpanel_api_key) ? '' : ($companyDetails->qpanel_api_key ?? '')); ?>">
                <small class="text-muted">Deixe em branco para manter a chave atual</small>
            </div>
            
            <!-- Subseção: Evolution API -->
            <div class="col-12">
                <h6 class="mb-3 mt-4">Evolution API</h6>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-evolution-api-url">Evolution API URL</label>
                <input type="text" class="form-control" id="ecommerce-settings-evolution-api-url" placeholder="<?php echo e(isset($companyDetails->evolution_api_url) ? 'Url oculta por motivos de segurança' : 'Evolution API URL'); ?>" name="evolution_api_url" aria-label="Evolution API URL" value="<?php echo e(isset($companyDetails->evolution_api_url) ? '' : ($companyDetails->evolution_api_url ?? '')); ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-evolution-api-key">Evolution API Key</label>
                <input type="text" class="form-control" id="ecommerce-settings-evolution-api-key" placeholder="<?php echo e(isset($companyDetails->evolution_api_key) ? 'Key oculta por motivos de segurança' : 'Evolution API Key'); ?>" name="evolution_api_key" aria-label="Evolution API Key" value="<?php echo e(isset($companyDetails->evolution_api_key) ? '' : ($companyDetails->evolution_api_key ?? '')); ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label mb-0" for="ecommerce-settings-api-version">API Version</label>
                <select class="form-select" id="ecommerce-settings-api-version" name="api_version" aria-label="API Version">
                    <option value="v1" <?php echo e(($companyDetails->api_version ?? 'v1') == 'v1' ? 'selected' : ''); ?>>v1</option>
                    <option value="v2" <?php echo e(($companyDetails->api_version ?? 'v1') == 'v2' ? 'selected' : ''); ?>>v2</option>
                </select>
            </div>
    
        </div>
        <?php endif; ?>
    </div>
</div>
          <div class="d-flex justify-content-end gap-3">
            <?php if(isset($companyDetails)): ?>
            <button type="submit" class="btn btn-primary">Atualizar</button>
          </form>
          <form action="<?php echo e(route('configuracoes.destroy', $companyDetails->id)); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="submit" class="btn btn-danger">Deletar</button>
          </form>
          <?php else: ?>
          <button type="reset" class="btn btn-label-secondary">Descartar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
          <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- /Options-->
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/developer.veetv.fun/resources/views/content/apps/configuracoes.blade.php ENDPATH**/ ?>