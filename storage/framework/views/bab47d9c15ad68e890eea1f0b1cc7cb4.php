<?php $__env->startSection('title', 'Criar Nova Campanha'); ?>

<?php $__env->startSection('vendor-style'); ?>
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/select2/select2.css')); ?>">
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
<script src="<?php echo e(asset('assets/vendor/libs/select2/select2.js')); ?>"></script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script>
    $(document).ready(function() {
        // Inicializar select2
        $('.select2').select2({
            placeholder: "Selecione...",
            allowClear: true
        });

        // Alternar entre origem de contatos
        $('input[name="origem_contatos"]').change(function() {
            if ($(this).val() === 'clientes') {
                $('#clientes-container').removeClass('d-none');
                $('#servidores-container').addClass('d-none');
            } else {
                $('#clientes-container').addClass('d-none');
                $('#servidores-container').removeClass('d-none');
            }
        });

        // Habilitar/desabilitar campo de data quando é recorrente
        $('#enviar_diariamente').change(function() {
            if ($(this).is(':checked')) {
                $('#data').val('').prop('disabled', true);
            } else {
                $('#data').prop('disabled', false);
            }
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h4 class="py-3 mb-2">
                <span class="text-muted fw-light"><?php echo e(config('variables.templateName', 'TemplateName')); ?> / </span>
                <a href="<?php echo e(route('campanhas.index')); ?>">Campanhas</a> / 
                Criar Nova
            </h4>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informações da Campanha</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo e(route('campanhas.store')); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>

                        <div class="row">
                            <!-- Nome da Campanha -->
                            <div class="col-md-12 mb-3">
                                <label for="nome" class="form-label">Nome da Campanha <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>

                            <!-- Data e Horário -->
                            <div class="col-md-6 mb-3">
                                <label for="horario" class="form-label">Horário <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="horario" name="horario" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="data" class="form-label">Data (opcional)</label>
                                <input type="date" class="form-control" id="data" name="data">
                            </div>

                            <!-- Recorrência -->
                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="enviar_diariamente" name="enviar_diariamente" value="1">
                                    <label class="form-check-label" for="enviar_diariamente">
                                        Enviar mensagem diariamente neste horário
                                    </label>
                                </div>
                            </div>

                            <!-- Origem dos Contatos -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Origem dos Contatos <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="origem_contatos" id="origem-clientes" value="clientes" checked>
                                    <label class="form-check-label" for="origem-clientes">Selecionar Clientes Individualmente</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="origem_contatos" id="origem-servidores" value="servidores">
                                    <label class="form-check-label" for="origem-servidores">Selecionar por Servidor</label>
                                </div>
                            </div>

                            <!-- Seleção de Clientes -->
                            <div class="col-md-12 mb-3" id="clientes-container">
                                <label for="contatos" class="form-label">Selecione os Clientes</label>
                                <select class="select2 form-select" id="contatos" name="contatos[]" multiple>
                                    <?php $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cliente): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($cliente->id); ?>"><?php echo e($cliente->nome); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            <!-- Seleção de Servidores -->
                            <div class="col-md-12 mb-3 d-none" id="servidores-container">
                                <label for="servidores" class="form-label">Selecione os Servidores</label>
                                <select class="select2 form-select" id="servidores" name="servidores[]" multiple>
                                    <?php $__currentLoopData = $servidores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $servidor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($servidor->id); ?>"><?php echo e($servidor->nome); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            <!-- Ignorar Contatos -->
                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ignorar_contatos" name="ignorar_contatos" value="1" checked>
                                    <label class="form-check-label" for="ignorar_contatos">
                                        Ignorar contatos que já receberam mensagem
                                    </label>
                                </div>
                            </div>

                            <!-- Mensagem -->
                            <div class="col-md-12 mb-3">
                                <label for="mensagem" class="form-label">Mensagem <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="mensagem" name="mensagem" rows="5" required></textarea>
                            </div>

                            <!-- Anexo -->
                            <div class="col-md-12 mb-3">
                                <label for="arquivo" class="form-label">Anexo (opcional)</label>
                                <input type="file" class="form-control" id="arquivo" name="arquivo">
                                <small class="text-muted">Formatos permitidos: jpg, jpeg, png, pdf, doc, docx, mp4, avi, mov, wmv (até 20MB)</small>
                            </div>

                            <!-- Botões -->
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary me-2">Salvar Campanha</button>
                                <a href="<?php echo e(route('campanhas.index')); ?>" class="btn btn-label-secondary">Cancelar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/gestor.veetv.fun/resources/views/campanhas/create.blade.php ENDPATH**/ ?>