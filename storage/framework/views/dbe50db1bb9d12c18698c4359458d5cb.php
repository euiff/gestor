<?php $__env->startSection('title', 'Fatura (Versão para Impressão) - Páginas'); ?>

<?php $__env->startSection('page-style'); ?>
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/css/pages/app-invoice-print.css')); ?>" />
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script src="<?php echo e(asset('assets/js/app-invoice-print.js')); ?>"></script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php
  use Carbon\Carbon;
  Carbon::setLocale('pt_BR'); // Configurar o locale para português do Brasil
  $statusMap = [
    'pending' => 'Pendente',
    'approved' => 'Aprovado',
    'cancelled' => 'Cancelado',
  ];
?>

<div class="invoice-print p-5">
  <div class="d-flex justify-content-between flex-row">
    <div class="mb-4">
      <div class="d-flex svg-illustration mb-3 gap-2">
        <?php echo $__env->make('_partials.macros',["height"=>20,"withbg"=>''], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <span class="app-brand-text fw-bold">
          <?php echo e($empresa->company_name); ?>

        </span>
      </div>
      <p class="mb-1">WhatsApp da Empresa: <?php echo e($empresa->company_whatsapp); ?></p>
    </div>
    <div>
      <h4 class="fw-medium">COBRANÇA #<?php echo e($payment->id); ?></h4>
      <div class="mb-2">
        <span class="text-muted">Data de Emissão:</span>
        <span class="fw-medium"><?php echo e($payment->created_at ? $payment->created_at->translatedFormat('d F, Y, H:i') : 'N/A'); ?></span>
      </div>
      <div>
        <span class="text-muted">Data de Vencimento:</span>
        <span class="fw-medium"><?php echo e($payment->due_date ? $payment->due_date->translatedFormat('d F, Y, H:i') : 'N/A'); ?></span>
      </div>
    </div>
  </div>

  <hr />

  <div class="row d-flex justify-content-between mb-4">
    <div class="col-sm-6 w-50">
      <h6>Cobrança Para:</h6>
      <p class="mb-1"><?php echo e($cliente->nome); ?></p>
      <p class="mb-1"><?php echo e($cliente->whatsapp); ?></p>
    </div>
    <div class="col-sm-6 w-50">
      <h6>Plano:</h6>
      <p class="mb-1"><?php echo e($plano->nome); ?></p>
      <p class="mb-1">Preço: <?php echo e($plano->preco); ?></p>
      <p class="mb-1">Duração: <?php echo e($plano->duracao); ?> dias</p>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table m-0">
      <thead class="table-light">
        <tr>
          <th>ID do Pagamento</th>
          <th>Valor</th>
          <th>Status</th>
          <th>Data de Criação</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?php echo e($payment->id); ?></td>
          <td><?php echo e($payment->valor); ?></td>
          <td>
            <?php if(isset($statusMap[$payment->status])): ?>
              <span class="badge bg-label-<?php echo e($payment->status == 'approved' ? 'success' : ($payment->status == 'pending' ? 'warning' : 'danger')); ?>">
                <?php echo e($statusMap[$payment->status]); ?>

              </span>
            <?php else: ?>
              <span class="badge bg-label-secondary">Desconhecido</span>
            <?php endif; ?>
          </td>
          <td><?php echo e($payment->created_at->translatedFormat('d F, Y, H:i')); ?></td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="row">
    <div class="col-12">
      <span class="fw-medium">Nota:</span>
      <span><?php echo e($payment->note); ?></span>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/gestor.veetv.fun/resources/views/content/apps/app-invoice-print.blade.php ENDPATH**/ ?>