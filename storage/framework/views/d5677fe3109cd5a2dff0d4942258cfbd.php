<?php $__env->startSection('title', 'Preview - Invoice'); ?>

<?php $__env->startSection('vendor-style'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/flatpickr/flatpickr.css')); ?>" />
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-style'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('assets/vendor/css/pages/app-invoice.css')); ?>" />
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <script src="<?php echo e(asset('assets/vendor/libs/moment/moment.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/vendor/libs/moment/locale/pt-br.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/vendor/libs/flatpickr/flatpickr.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/vendor/libs/flatpickr/l10n/pt.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/vendor/libs/cleavejs/cleave.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/vendor/libs/cleavejs/cleave-phone.js')); ?>"></script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <script src="<?php echo e(asset('assets/js/offcanvas-add-payment.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/js/offcanvas-send-invoice.js')); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar Flatpickr para usar o idioma pt
            flatpickr.localize(flatpickr.l10ns.pt);
            flatpickr(".flatpickr", {
                dateFormat: "d M, Y"
            });

            // Configurar Moment.js para usar o idioma pt-br
            moment.locale('pt-br');
        });
    </script>
    <script>
        function showLoadingModal() {
          var loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
          loadingModal.show();
        }
      </script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php if(session('error')): ?>
    <div class="alert alert-danger">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>
        <style>
        .bg-label-warning {
    background-color: #7367f0 !important;
    color: white!important;
    }
    </style>
    <div class="row invoice-preview">
               <!-- Invoice -->
        <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-4">
            <div class="card invoice-preview-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column m-sm-3 m-0">
                        <div class="mb-xl-0 mb-4">
                            <div class="d-flex svg-illustration mb-4 gap-2 align-items-center">
                                <?php echo $__env->make('_partials.macros', ['height' => 20, 'withbg' => ''], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                <span class="app-brand-text fw-bold fs-4">
                                    <?php echo e($empresa->company_name); ?>

                                </span>
                            </div>
                            <p class="mb-2">WhatsApp da Empresa:</p>
                            <p class="mb-2"><?php echo e($empresa->company_whatsapp); ?></p>
                        </div>
                        <div>
                            <?php
                            use Carbon\Carbon;
                            Carbon::setLocale('pt_BR');
                            ?>
                            <h4 class="fw-medium mb-2">Cobrança #<?php echo e($payment->id); ?></h4>
                            <div class="mb-2 pt-1">
                                <span>Data de Emissão:</span>
                                <span class="fw-medium">
                                    <?php echo e($payment->created_at ? $payment->created_at->translatedFormat('d/m/Y') : 'N/A'); ?>

                                </span>
                            </div>
                            <div class="pt-1">
                                <span>Data de Vencimento:</span>
                                <span class="fw-medium">
                                    <?php echo e($cliente->vencimento ? \Carbon\Carbon::parse($cliente->vencimento)->format('d/m/Y') : 'N/A'); ?>

                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="my-0" />
                <div class="card-body">
                    <div class="row p-sm-3 p-0">
                        <div class="col-xl-6 col-md-12 col-sm-5 col-12 mb-xl-0 mb-md-4 mb-sm-0 mb-4">
                            <h6 class="mb-3">Cobrança Para:</h6>
                            <p class="mb-1"><?php echo e($cliente->nome); ?></p>
                            <p class="mb-1"><?php echo e($cliente->whatsapp); ?></p>
                        </div>
                        <div class="col-xl-6 col-md-12 col-sm-7 col-12">
                            <h6 class="mb-4">Plano:</h6>
                            <p class="mb-1"><?php echo e($plano->nome); ?></p>
                            <p class="mb-1">Preço: R$<?php echo e($plano->preco); ?></p>
                            <p class="mb-1">Duração: <?php echo e($plano->duracao); ?> dias</p>
                        </div>
                    </div>
                </div>
                <div class="table-responsive border-top">
                    <table class="table m-0">
                        <thead>
                            <tr>
                                <th>ID do Pagamento</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Data de Criação</th>
                                <th>Data de Pagamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo e($payment->id); ?></td>
                                <td>R$<?php echo e($payment->valor); ?></td>
                                <td>
                                    <?php if($payment->status == 'approved'): ?>
                                        <span class="badge bg-label-success">Aprovado</span>
                                    <?php elseif($payment->status == 'pending'): ?>
                                        <span class="badge bg-label-warning">Pendente</span>
                                    <?php elseif($payment->status == 'cancelled'): ?>
                                        <span class="badge bg-label-danger">Cancelado</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($payment->created_at ? $payment->created_at->translatedFormat('d/m/Y') : 'N/A'); ?></td>
                                <td><?php echo e($payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : 'Aguardando Pagamento'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
        
                <?php if(!empty($cliente->notas)): ?>
                    <div class="card-body mx-3">
                        <div class="row">
                            <div class="col-12">
                                <span class="fw-medium">Nota:</span>
                                <span><?php echo e($cliente->notas); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- /Invoice -->

        <!-- Invoice Actions -->
        <div class="col-xl-3 col-md-4 col-12 invoice-actions">
            <div class="card">
                <div class="card-body">
                    <!-- <button class="btn btn-primary d-grid w-100 mb-2" data-bs-toggle="offcanvas"
                  data-bs-target="#sendInvoiceOffcanvas">
                  <span class="d-flex align-items-center justify-content-center text-nowrap"><i
                      class="ti ti-send ti-xs me-2"></i>Enviar Cobrança</span>
                </button> -->

                    <a class="btn btn-label-secondary d-grid w-100 mb-2" target="_blank"
                        href="<?php echo e(url('app/invoice/print', ['payment_id' => $payment->id])); ?>">
                        Imprimir
                    </a>
                    <!-- <a href="<?php echo e(url('app/invoice/edit')); ?>" class="btn btn-label-secondary d-grid w-100 mb-2">
                  Editar Cobrança
                </a> -->
                    <button class="btn btn-primary d-grid w-100" data-bs-toggle="offcanvas"
                        data-bs-target="#addPaymentOffcanvas">
                        <span class="d-flex align-items-center justify-content-center text-nowrap"><i
                                class="ti ti-currency-dollar ti-xs me-2"></i>Adicionar Pagamento</span>
                    </button>
                </div>
            </div>
        </div>
        <!-- /Invoice Actions -->
    </div>

    <!-- Offcanvas -->
    <?php echo $__env->make('_partials/_offcanvas/offcanvas-send-invoice', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('_partials/_offcanvas/offcanvas-add-payment', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <!-- /Offcanvas -->
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/gestor.veetv.fun/resources/views/content/apps/detalhes.blade.php ENDPATH**/ ?>