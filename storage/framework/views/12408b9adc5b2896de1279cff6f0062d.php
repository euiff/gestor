<?php $__env->startSection('title', 'Servidores'); ?>

<?php
    $visibleColumns = getUserPreferences('servidores');
?>

<?php $__env->startSection('page-script'); ?>
<script>
    var destroyMultipleUrl = '<?php echo e(route('servidores.deletarMultiplos')); ?>';
    var label_update = '<?php echo e(__('messages.update')); ?>';
    var label_delete = '<?php echo e(__('messages.delete')); ?>';
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo e(asset('assets/js/pages/servidores.js')); ?>"></script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mt-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            
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
                            data-url="<?php echo e(route('servidores.list')); ?>" data-icons-prefix="bx" data-icons="icons"
                            data-show-refresh="true" data-total-field="total" data-trim-on-search="false"
                            data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                            data-side-pagination="server" data-show-columns="true" data-pagination="true"
                            data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                            data-query-params="queryParams" data-route-prefix="<?php echo e(Route::getCurrentRoute()->getPrefix()); ?>">

                            <thead>
                                <tr>
                                    <th data-checkbox="true"></th>
                                    <th data-field="id"
                                        data-visible="<?php echo e(in_array('id', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>"
                                        data-sortable="true">ID</th>
                                    <th data-field="nome"
                                        data-visible="<?php echo e(in_array('nome', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>"
                                        data-sortable="true">Nome</th>
                                    <th data-field="clientes_count"
                                        data-visible="<?php echo e(in_array('clientes_count', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>"
                                        data-sortable="true">Número de Clientes</th>
                                    <th data-field="created_at"
                                        data-visible="<?php echo e(in_array('created_at', $visibleColumns) ? 'true' : 'false'); ?>"
                                        data-sortable="true">Criado em</th>
                                    <th data-field="updated_at"
                                        data-visible="<?php echo e(in_array('updated_at', $visibleColumns) ? 'true' : 'false'); ?>"
                                        data-sortable="true">Atualizado em</th>
                                    <th data-field="actions"
                                        data-visible="<?php echo e(in_array('actions', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false'); ?>">
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
                    <form id="addServidorForm" class="row g-3" action="<?php echo e(route('servidores.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
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
    <?php $__currentLoopData = $servidores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $servidor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="modal fade" id="deleteServidor<?php echo e($servidor->id); ?>" tabindex="-1" aria-labelledby="deleteServidor<?php echo e($servidor->id); ?>Label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteServidor<?php echo e($servidor->id); ?>Label">Excluir Servidor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Tem certeza que deseja excluir o servidor <strong><?php echo e($servidor->nome); ?></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form action="<?php echo e(route('servidores.destroy', $servidor->id)); ?>" method="POST" style="display:inline;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-danger">Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <!-- Modal de Edição -->
    <?php $__currentLoopData = $servidores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $servidor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="modal fade" id="editServidor<?php echo e($servidor->id); ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-simple modal-edit-servidor">
                <div class="modal-content p-3 p-md-5">
                    <div class="modal-body">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Editar Servidor</h3>
                            <p class="text-muted">Atualize os detalhes do servidor.</p>
                        </div>
                        <form id="editServidorForm<?php echo e($servidor->id); ?>" class="row g-3"
                            action="<?php echo e(route('servidores.update', $servidor->id)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>
                            <div class="col-12">
                                <label class="form-label" for="editServidorNome<?php echo e($servidor->id); ?>">Nome</label>
                                <input type="text" id="editServidorNome<?php echo e($servidor->id); ?>" name="nome"
                                    class="form-control" value="<?php echo e($servidor->nome); ?>" required />
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
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/gestor.veetv.fun/resources/views/servidores/index.blade.php ENDPATH**/ ?>