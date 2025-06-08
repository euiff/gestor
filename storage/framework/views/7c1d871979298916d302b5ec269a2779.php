<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('vendor-style'); ?>
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/apex-charts/apex-charts.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')); ?>" />
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-style'); ?>
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/css/pages/app-logistics-dashboard.css')); ?>" />
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
<script src="<?php echo e(asset('assets/vendor/libs/apex-charts/apexcharts.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')); ?>"></script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script src="<?php echo e(asset('assets/js/app-logistics-dashboard.js')); ?>"></script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Clientes /</span> Dashboard
</h4>

<!-- Card Border Shadow -->
<div class="row">
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2 pb-1">
          <div class="avatar me-2">
            <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-shopping-cart ti-md"></i></span>
          </div>
          <h4 class="ms-1 mb-0"><?php echo e($totalCompras); ?></h4>
        </div>
        <p class="mb-1">Total de Compras</p>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-warning">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2 pb-1">
          <div class="avatar me-2">
            <span class="avatar-initial rounded bg-label-warning"><i class='ti ti-alert-triangle ti-md'></i></span>
          </div>
          <h4 class="ms-1 mb-0"><?php echo e($comprasPendentes); ?></h4>
        </div>
        <p class="mb-1">Compras Pendentes</p>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-danger">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2 pb-1">
          <div class="avatar me-2">
            <span class="avatar-initial rounded bg-label-danger"><i class='ti ti-git-fork ti-md'></i></span>
          </div>
          <h4 class="ms-1 mb-0"><?php echo e($comprasCanceladas); ?></h4>
        </div>
        <p class="mb-1">Compras Canceladas</p>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-info">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2 pb-1">
          <div class="avatar me-2">
            <span class="avatar-initial rounded bg-label-info"><i class='ti ti-clock ti-md'></i></span>
          </div>
          <h4 class="ms-1 mb-0"><?php echo e($comprasAtrasadas); ?></h4>
        </div>
        <p class="mb-1">Compras Aprovadas</p>
      </div>
    </div>
  </div>
</div>

<!-- Detalhes da Assinatura do Cliente -->
<div class="row">
  <?php if($cliente): ?>
    <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card card-border-shadow-primary">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-primary"><i class='ti ti-user ti-md'></i></span>
            </div>
            <h4 class="ms-1 mb-0"><?php echo e($cliente->nome); ?></h4>
          </div>
          <p class="mb-1">Nome</p>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card card-border-shadow-secondary">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-secondary"><i class='ti ti-phone ti-md'></i></span>
            </div>
            <h4 class="ms-1 mb-0"><?php echo e($cliente->whatsapp); ?></h4>
          </div>
          <p class="mb-1">WhatsApp</p>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card card-border-shadow-success">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-success"><i class='ti ti-calendar ti-md'></i></span>
            </div>
            <h4 class="ms-1 mb-0"><?php echo e(\Carbon\Carbon::parse($cliente->vencimento)->format('d/m/Y')); ?></h4>
          </div>
          <p class="mb-1">Vencimento</p>
        </div>
      </div>
    </div>
        <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card card-border-shadow-danger">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-danger"><i class='ti ti-package ti-md'></i></span>
            </div>
            <h4 class="ms-1 mb-0"><?php echo e($cliente->plano->nome); ?></h4>
          </div>
          <p class="mb-1">Plano</p>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card card-border-shadow-warning">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-warning"><i class='ti ti-quote ti-md'></i></span>
            </div>
            <h4 class="ms-1 mb-0"><?php echo e($cliente->numero_de_telas); ?></h4>
          </div>
          <p class="mb-1">Número de Telas</p>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card card-border-shadow-info">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-info"><i class='ti ti-notes ti-md'></i></span>
            </div>
            <h4 class="ms-1 mb-0"><?php echo e($cliente->notas); ?></h4>
          </div>
          <p class="mb-1">Notas</p>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="col-12">
      <div class="card card-border-shadow-info">
        <div class="card-body">
          <p>Detalhes do cliente não encontrados.</p>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/developer.veetv.fun/resources/views/client/dashboard.blade.php ENDPATH**/ ?>