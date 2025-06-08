<?php $__env->startSection('title', 'Gerenciar Clientes'); ?>

<?php
    $visibleColumns = getUserPreferences('clientes');
    $type = 'clientes';
    $planoAtual = $planos_revenda->firstWhere('id', $user->plano_id);
    $limitePlano = $planoAtual ? $planoAtual->limite : 0;
?>

<?php $__env->startSection('page-script'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuração mais robusta do tooltip
    var tooltip = new bootstrap.Tooltip(document.getElementById('syncQpanelTooltip'), {
        html: true,
        trigger: 'hover focus',
        delay: { "show": 500, "hide": 100 },
        boundary: 'window'
    });
});
</script>

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

    // Função de máscara para telefone
    function masktel(v) {
        v = v.replace(/\D/g, ""); // Remove tudo que não é dígito
        v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); // Coloca parênteses em volta dos dois primeiros dígitos
        v = v.replace(/(\d)(\d{4})$/, "$1-$2"); // Coloca hífen antes dos últimos 4 dígitos
        return v;
    }

    // Validação do campo de WhatsApp
    document.getElementById('addClientWhatsApp').addEventListener('blur', function() {
        const phoneInput = this.value.replace(/\D/g, ''); // Remove todos os caracteres não numéricos
        const phoneError = document.createElement('div'); // Cria um elemento para exibir o erro
        phoneError.className = 'text-danger mt-2';
        phoneError.innerText = 'O número do WhatsApp é obrigatório e deve ter 11 dígitos.';

        // Remove mensagens de erro anteriores
        const existingError = this.parentElement.querySelector('.text-danger');
        if (existingError) {
            existingError.remove();
        }

        if (phoneInput.length < 11) { // Verifica se o número tem 11 dígitos
            this.parentElement.appendChild(phoneError);
        }
    });
    var loadDataUrl = '<?php echo e(route('app-ecommerce-customer-list')); ?>';
    var label_update = '<?php echo e(__('messages.update')); ?>';
    var label_delete = '<?php echo e(__('messages.delete')); ?>';
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo e(asset('assets/js/pages/clientes.js')); ?>"></script>


<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <!-- Verificação de Mensagens de Sessão -->
        <?php if(session('warning')): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <?php echo e(session('warning')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- mensagens para erros -->
        <?php if(session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo e(session('error')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- mensagens para sucesso -->
        <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo e(session('success')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Card colapsável para a URL de login -->
        <div class="accordion" id="loginUrlAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        URL de Login para Clientes
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne"
                    data-bs-parent="#loginUrlAccordion">
                    <div class="accordion-body">
                        <p>Compartilhe esta URL com seus clientes para que eles possam acessar a área de login.</p>
                        <div class="mb-3">
                            <label>URL de Login</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="loginUrl" value="<?php echo e($loginUrl); ?>" readonly>
                                <button class="btn btn-outline-secondary" type="button" id="copyButton">Copiar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <div class="d-flex justify-content-end">
                <?php if($user->role_id == 1): ?>
                    <span class="badge bg-success mb-3">Créditos: ∞</span>
                <?php else: ?>
                    <span class="badge bg-success mb-3">Limite de Clientes: <?php echo e($limitePlano); ?></span>
                <?php endif; ?>
            </div>

        <!-- Botão para abrir o modal de adicionar cliente -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addClient">Adicionar Cliente</button>

        <!-- Botão para importar clientes -->
        <button class="btn btn-secondary mb-3" data-bs-toggle="modal" data-bs-target="#importClients">Importar Clientes</button>

        <!-- Botão para exportar clientes -->
        <a href="#" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#exportModal">Exportar Clientes</a>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <select class="form-select" id="client_status_filter" aria-label="Default select example">
                            <option value="">Selecionar vencimento</option>
                            <option value="todos">Todos</option>
                            <option value="vencido">Vencido</option>
                            <option value="hoje">Vence hoje</option>
                            <option value="ainda_vai_vencer">Ainda vai vencer</option>        
                        </select>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive text-nowrap">
                        <input type="hidden" id="data_type" value="clientes">
                        <input type="hidden" id="save_column_visibility" name="visible_columns">
                        <div class="fixed-table-toolbar">
                        </div>
                        <table id="table" data-toggle="table" data-loading-template="loadingTemplate"
                            data-url="<?php echo e(route('app-ecommerce-customer-list')); ?>" data-icons-prefix="bx" data-icons="icons"
                            data-show-refresh="true" data-total-field="total" data-trim-on-search="false"
                            data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                            data-side-pagination="server" data-show-columns="true" data-pagination="true"
                            data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                            data-query-params="queryParams"
                            data-route-prefix="<?php echo e(Route::getCurrentRoute()->getPrefix()); ?>">
                
                            <thead>
                                <tr>
                                    <th data-checkbox="true"></th>
                                    <th data-sortable="true" data-field="id">ID</th>
                                    <th data-field="nome" data-visible="<?php echo e(in_array('nome', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>" data-sortable="true">Cliente</th>
                                    <th data-field="iptv_nome" data-visible="<?php echo e(in_array('iptv_nome', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>" data-sortable="true">Usuário IPTV</th>
                                    <th data-field="whatsapp" data-visible="<?php echo e(in_array('whatsapp', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>" data-sortable="true">WhatsApp</th>
                                    <th data-field="vencimento" data-visible="<?php echo e(in_array('vencimento', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>" data-sortable="true">Vencimento</th>
                                    <th data-field="servidor" data-visible="<?php echo e(in_array('servidor', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>" data-sortable="true">Servidor</th>
                                    <th data-field="notificacoes" data-visible="<?php echo e(in_array('notificacoes', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>" data-sortable="true">Notificações</th>
                                    <th data-field="sync_qpanel" data-visible="<?php echo e(in_array('sync_qpanel', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>" data-sortable="true">Sinc. Qpanel</th>
                                    <th data-field="plano" data-visible="<?php echo e(in_array('plano', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>" data-sortable="true">Plano</th>
                                    <th data-field="valor" data-visible="<?php echo e(in_array('valor', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>" data-sortable="true">Valor</th>
                                    <th data-field="numero_de_telas" data-visible="<?php echo e(in_array('numero_de_telas', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>" data-sortable="true">Conexões</th>
                                    <th data-field="notas" data-visible="<?php echo e(in_array('notas', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>" data-sortable="true">Notas</th>
                                    <th data-field="actions" data-visible="<?php echo e(in_array('actions', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>">Ações</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para adicionar novo cliente -->
    <div class="modal fade" id="addClient" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-simple modal-add-client">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h3 class="mb-2">Adicionar Novo Cliente</h3>
                        <p class="text-muted">Preencha os detalhes do novo cliente.</p>
                    </div>
                    <form id="addClientForm" class="row g-3" action="<?php echo e(route('app-ecommerce-customer-store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
    
                        <!-- Campos ocultos para enganar o autocomplete do navegador -->
                        <input type="text" name="fakeusernameremembered" style="display:none;">
                        <input type="password" name="fakepasswordremembered" style="display:none;">
    
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="addClientNome">Nome do Cliente</label>
                            <input type="text" id="addClientNome" name="nome" class="form-control" placeholder="Nome" required autocomplete="off" />
                        </div>
    
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="addClientPassword">Senha</label>
                            <div class="input-group">
                                <input type="password" id="addClientPassword" name="password" class="form-control" placeholder="Senha" required autocomplete="new-password" />
                                <button type="button" class="btn btn-outline-secondary" onclick="generatePassword('addClientPassword')">
                                    <i class="fas fa-random"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('addClientPassword')">
                                    <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                        </div>
    
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="addClientIPTVNome">Usuário IPTV</label>
                            <input type="text" id="addClientIPTVNome" name="iptv_nome" class="form-control" placeholder="Opcional" autocomplete="off" />
                        </div>
    
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="addClientIPTVSenha">Senha IPTV</label>
                            <div class="input-group">
                                <input type="text" id="addClientIPTVSenha" name="iptv_senha" class="form-control" placeholder="Opcional" autocomplete="off" />
                                <button type="button" class="btn btn-outline-secondary" onclick="generatePassword('addClientIPTVSenha')">
                                    <i class="fas fa-random"></i> 
                                </button>
                            </div>
                        </div>
    
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="addClientWhatsApp">WhatsApp</label>
                            <input type="text" id="addClientWhatsApp" name="whatsapp" class="form-control" maxlength="15" placeholder="WhatsApp" required oninput="mask(this, masktel)" autocomplete="off" />
                        </div>
    
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="addClientVencimento">Vencimento</label>
                            <input type="date" id="addClientVencimento" name="vencimento" class="form-control" placeholder="Vencimento" required />
                        </div>
    
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="addClientServidor">Servidor</label>
                            <select id="addClientServidor" name="servidor_id" class="form-select" required>
                                <!-- Servidores serão carregados via AJAX -->
                            </select>
                        </div>
    
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="addClientNotificacoes">Notificações</label>
                            <select id="addClientNotificacoes" name="notificacoes" class="form-select" required>
                                <option value="1">Sim</option>
                                <option value="0">Não</option>
                            </select>
                        </div>
    
                        <div class="col-12 col-md-6 position-relative">  <!-- Adicionei position-relative aqui -->
                            <label class="form-label" for="addClientSyncQpanel">
                                Sincronizar Qpanel
                                <i class="fa-solid fa-circle-info"
                                   id="syncQpanelInfoIcon"
                                   aria-hidden="true"></i>
                            </label>
                            <select id="addClientSyncQpanel" name="sync_qpanel" class="form-select" required>
                                <option value="1">Sim</option>
                                <option value="0">Não</option>
                            </select>
                            
                            <!-- Tooltip customizado -->
                            <div class="custom-tooltip shadow" id="syncQpanelTooltip">
                                Com essa opção ativa, o cliente em seu painel Veetv será sincronizado automaticamente, trazendo a data de vencimento do Qpanel e, ao ser renovado no gestor, o cliente tambem será renovado no Qpanel, desde que você tenha crédito suficiente em seu painel. Lembre-se de inserir seu nome de usuário nas <a href="/configuracoes" class="tooltip-link">Configurações</a>.
                            </div>
                        </div>
                        
                        <style>
                            .custom-tooltip {
                                position: absolute;
                                z-index: 9999;
                                width: 300px;
                                padding: 15px;
                                background: #2f3349;
                                border-radius: 5px;
                                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                                display: none;
                                font-size: 14px;
                                line-height: 1.5;
                                top: 100%;  /* Posiciona logo abaixo do elemento pai */
                                left: 0;
                                margin-top: 5px;  /* Pequeno espaçamento do ícone */
                            }
                            
                            .custom-tooltip .tooltip-link {
                                color: #7367f0;
                                text-decoration: underline;
                            }
                            
                            @media (max-width: 768px) {
                                .custom-tooltip {
                                    width: 90%;
                                    left: 5% !important;
                                    right: 5% !important;
                                }
                            }
                        </style>
                        
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const infoIcon = document.getElementById('syncQpanelInfoIcon');
                            const tooltip = document.getElementById('syncQpanelTooltip');
                            let tooltipTimeout;
                            
                            // Desktop - hover
                            infoIcon.addEventListener('mouseenter', function() {
                                clearTimeout(tooltipTimeout);
                                tooltip.style.display = 'block';
                            });
                            
                            infoIcon.addEventListener('mouseleave', function() {
                                tooltipTimeout = setTimeout(() => {
                                    tooltip.style.display = 'none';
                                }, 300);
                            });
                            
                            // Mobile - touch
                            infoIcon.addEventListener('click', function(e) {
                                e.preventDefault();
                                clearTimeout(tooltipTimeout);
                                
                                if (tooltip.style.display === 'block') {
                                    tooltip.style.display = 'none';
                                } else {
                                    tooltip.style.display = 'block';
                                }
                            });
                            
                            // Impede que o tooltip feche quando hover nele mesmo
                            tooltip.addEventListener('mouseenter', function() {
                                clearTimeout(tooltipTimeout);
                            });
                            
                            tooltip.addEventListener('mouseleave', function() {
                                tooltipTimeout = setTimeout(() => {
                                    tooltip.style.display = 'none';
                                }, 300);
                            });
                            
                            // Fecha o tooltip ao clicar fora
                            document.addEventListener('click', function(e) {
                                if (!infoIcon.contains(e.target) && !tooltip.contains(e.target)) {
                                    tooltip.style.display = 'none';
                                }
                            });
                        });
                        </script>
    
                        <div class="col-12 col-md-6 position-relative">
                            <label class="form-label" for="addClientPlano">
                                Plano
                                <i class="fa-solid fa-circle-info"
                                   id="planoInfoIcon"
                                   aria-hidden="true"></i>
                            </label>
                            <select id="addClientPlano" name="plano_id" class="form-select">
                                <!-- Suas opções existentes permanecem aqui -->
                            </select>
                            
                            <!-- Tooltip customizado -->
                            <div class="custom-tooltip shadow" id="planoTooltip">
                                Com a função <strong>Sincronizar Qpanel</strong> ativa, o plano é criado automaticamente de acordo com o plano cadastrado ao cliente no Qpanel.<br><br>
                                Exemplo: Se o cliente possui o Plano Mensal (C/ Adultos) no Qpanel, o plano Mensal (C/ Adultos) também será criado no Gestor.
                            </div>
                        </div>
                        
                        <style>
                            /* Adicione apenas se não existir */
                            .custom-tooltip {
                                position: absolute;
                                z-index: 9999;
                                width: 300px;
                                padding: 15px;
                                background: #2f3349;
                                color: white;
                                border-radius: 5px;
                                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                                display: none;
                                font-size: 14px;
                                line-height: 1.5;
                                top: 100%;
                                left: 0;
                                margin-top: 5px;
                            }
                            
                            @media (max-width: 768px) {
                                .custom-tooltip {
                                    width: 90%;
                                    left: 5% !important;
                                    right: 5% !important;
                                }
                            }
                        </style>
                        
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Configuração do tooltip do plano
                            const planoInfoIcon = document.getElementById('planoInfoIcon');
                            const planoTooltip = document.getElementById('planoTooltip');
                            let planoTooltipTimeout;
                            
                            // Desktop - hover
                            planoInfoIcon.addEventListener('mouseenter', function() {
                                clearTimeout(planoTooltipTimeout);
                                planoTooltip.style.display = 'block';
                            });
                            
                            planoInfoIcon.addEventListener('mouseleave', function() {
                                planoTooltipTimeout = setTimeout(() => {
                                    planoTooltip.style.display = 'none';
                                }, 300);
                            });
                            
                            // Mobile - touch
                            planoInfoIcon.addEventListener('click', function(e) {
                                e.preventDefault();
                                clearTimeout(planoTooltipTimeout);
                                planoTooltip.style.display = planoTooltip.style.display === 'block' ? 'none' : 'block';
                            });
                            
                            // Fecha ao clicar fora
                            document.addEventListener('click', function(e) {
                                if (!planoInfoIcon.contains(e.target) && !planoTooltip.contains(e.target)) {
                                    planoTooltip.style.display = 'none';
                                }
                            });
                        });
                        </script>
    
                        <div class="col-12">
                            <label class="form-label" for="addClientNumeroDeTelas">Número de Telas</label>
                            <input type="number" id="addClientNumeroDeTelas" name="numero_de_telas" class="form-control" value="1" placeholder="Número de Telas" required />
                        </div>
    
                        <div class="col-12">
                            <label class="form-label" for="addClientNotas">Notas</label>
                            <textarea id="addClientNotas" name="notas" class="form-control" placeholder="Notas"></textarea>
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

<script>
function generatePassword(fieldId) {
    let length = 8,
        charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*",
        password = "";
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    document.getElementById(fieldId).value = password;
}

function togglePasswordVisibility(fieldId) {
    let input = document.getElementById(fieldId);
    let icon = document.getElementById("togglePasswordIcon");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}
</script>

    <!-- Modal para importar clientes -->
    <div class="modal fade" id="importClients" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-simple modal-import-clients">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h3 class="mb-2">Importar Clientes</h3>
                        <p class="text-muted">Faça upload de um arquivo CSV para importar clientes.</p>
                    </div>
                    <form id="importClientsForm" class="row g-3" action="<?php echo e(route('app-ecommerce-customer-import')); ?>"
                        method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <div class="col-12">
                            <label class="form-label" for="importClientsFile">Arquivo CSV</label>
                            <input type="file" id="importClientsFile" name="file" class="form-control"
                                accept=".csv" required />
                        </div>
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Importar</button>
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

    <!-- Modal para exportar clientes -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-simple modal-export-clients">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h3 class="mb-2">Exportar Clientes</h3>
                        <p class="text-muted">Escolha o formato para exportar os clientes.</p>
                    </div>
                    <form id="exportClientsForm" class="row g-3" action="<?php echo e(route('app-ecommerce-customer-export')); ?>" method="GET">
                        <div class="col-12">
                            <label class="form-label" for="exportFormat">Formato de Exportação</label>
                            <select id="exportFormat" name="format" class="form-select" required>
                                <option value="csv">CSV</option>
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Exportar</button>
                            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                        </div>
                    </form>
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
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/developer.veetv.fun/resources/views/content/apps/app-ecommerce-customer-all.blade.php ENDPATH**/ ?>