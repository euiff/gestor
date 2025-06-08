<?php
$configData = Helper::appClasses();
$isFront = true;
?>

<?php $__env->startSection('layoutContent'); ?>



<?php echo $__env->make('layouts/sections/navbar/navbar-front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<!-- Sections:Start -->
<?php echo $__env->yieldContent('content'); ?>
<!-- / Sections:End -->

<?php echo $__env->make('layouts/sections/footer/footer-front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/commonMaster' , \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/gestor.veetv.fun/resources/views/layouts/layoutFront.blade.php ENDPATH**/ ?>