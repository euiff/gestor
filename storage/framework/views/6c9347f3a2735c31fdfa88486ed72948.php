<?php $__env->startSection('title', 'Comprovantes de Compras - Cliente'); ?>

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
<script>
  document.getElementById('applyFilterBtn').addEventListener('click', function() {
    const status = document.getElementById('filterStatus').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;

    const url = new URL('<?php echo e(route('cliente.comprovantes')); ?>');
    const params = {
        status: status,
        date_from: dateFrom,
        date_to: dateTo
    };

    Object.keys(params).forEach(key => {
        if (params[key]) {
            url.searchParams.append(key, params[key]);
        }
    });

    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const tbody = document.querySelector('table tbody');
        tbody.innerHTML = '';

        data.compras.forEach(compra => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${compra.id}</td>
                <td>${new Date(compra.created_at).toLocaleDateString('pt-BR')}</td>
                <td><span class="badge bg-${compra.status == 'approved' ? 'success' : (compra.status == 'pending' ? 'warning' : 'danger')}">${compra.status.charAt(0).toUpperCase() + compra.status.slice(1)}</span></td>
                <td>${parseFloat(compra.valor).toFixed(2).replace('.', ',')}</td>
            `;
            tbody.appendChild(tr);
        });

        const tfoot = document.querySelector('table tfoot th:last-child');
        tfoot.textContent = data.total.toFixed(2).replace('.', ',');
    })
    .catch(error => console.error('Error:', error));
});
</script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Gerenciar /</span> Comprovantes de Compras
</h4>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Compras do Cliente</h5>
        <div class="card-tools">
          <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
            <i class="ti ti-filter"></i> Filtrar
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead class="table-dark">
              <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Status</th>
                <th>Valor</th>
              </tr>
            </thead>
            <tbody>
              <?php $__currentLoopData = $compras; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $compra): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                  <td><?php echo e($compra->id); ?></td>
                  <td><?php echo e(\Carbon\Carbon::parse($compra->created_at)->format('d/m/Y')); ?></td>
                  <td>
                    <span class="badge bg-<?php echo e($compra->status == 'approved' ? 'success' : ($compra->status == 'pending' ? 'warning' : 'danger')); ?>">
                      <?php echo e($statusMap[$compra->status]); ?>

                    </span>
                  </td>
                  <td><?php echo e(number_format($compra->valor, 2, ',', '.')); ?></td>
                </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="3" class="text-end">Total:</th>
                <th><?php echo e(number_format($compras->sum('valor'), 2, ',', '.')); ?></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Filtro -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="filterModalLabel">Filtrar Compras</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="mb-3">
            <label for="filterStatus" class="form-label">Status</label>
            <select class="form-select" id="filterStatus">
              <option value="">Todos</option>
              <option value="approved">Aprovado</option>
              <option value="pending">Pendente</option>
              <option value="cancelled">Cancelado</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="filterDateFrom" class="form-label">Data de</label>
            <input type="date" class="form-control" id="filterDateFrom">
          </div>
          <div class="mb-3">
            <label for="filterDateTo" class="form-label">Data até</label>
            <input type="date" class="form-control" id="filterDateTo">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-primary" id="applyFilterBtn">Aplicar Filtro</button>
      </div>
    </div>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/developer.veetv.fun/resources/views/client/comprovantes.blade.php ENDPATH**/ ?>