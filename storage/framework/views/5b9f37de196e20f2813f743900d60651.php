<?php
$containerFooter = (isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact') ? 'container-xxl' : 'container-fluid';
?>

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
  <div class="<?php echo e($containerFooter); ?>">
    <div class="footer-container d-flex align-items-center justify-content-between py-2 flex-md-row flex-column">
      <div>
        © <script>
          document.write(new Date().getFullYear())
        </script>
        <a href="<?php echo e((!empty(config('variables.creatorUrl')) ? config('variables.creatorUrl') : '')); ?>" target="_blank" class="fw-medium"><?php echo e((!empty(config('variables.templateName')) ? config('variables.templateName') : '')); ?></a>
        <span class="footer-text"> | Versão <?php echo e(env('APP_VERSION', '1.0.0')); ?></span>
      </div>
    </div>
  </div>
</footer>
<!--/ Footer-->
<?php /**PATH /www/wwwroot/developer.veetv.fun/resources/views/layouts/sections/footer/footer.blade.php ENDPATH**/ ?>