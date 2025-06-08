<?php $__env->startSection('title', 'Sessions'); ?>

<?php $__env->startSection('content'); ?>
<h4 class="py-3 mb-2">
  <span class="text-muted fw-light">Admin /</span> Sessões Ativas
</h4>

<table class="table table-striped">
    <thead>
        <tr>
            <th>User ID</th>
            <th>IP Address</th>
            <th>Location</th>
            <th>Last Activity</th>
        </tr>
    </thead>
    <tbody>
        <?php $__currentLoopData = $sessions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $session): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
             <td><?php echo e($session->user_name); ?></td>
            <td><?php echo e($session->ip_address); ?></td>
            <td><?php echo e($session->location); ?></td>
            <td><?php echo e(\Carbon\Carbon::createFromTimestamp($session->last_activity)->format('d/m/Y H:i:s')); ?></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/gestor.veetv.fun/resources/views/admin/sessions/index.blade.php ENDPATH**/ ?>