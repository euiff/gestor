<!-- Footer: Start -->
<footer class="landing-footer bg-body footer-text">
  <div class="footer-top">
    <div class="container">
      <div class="row gx-0 gy-4 g-md-5">
        <div class="col-lg-5">
          <a href="<?php echo e(url('front-pages/landing')); ?>" class="app-brand-link mb-4">
            <span class="app-brand-logo demo"><?php echo $__env->make('_partials.macros',['height'=>20,'withbg' => "fill: #fff;"], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></span>
            <span class="app-brand-text demo footer-link fw-bold ms-2 ps-1"><?php echo e(config('variables.templateName')); ?></span>
          </a>
        </div>
      </div>
    </div>
  </div>
    <div class="footer-bottom py-3">
      <div class="container d-flex flex-wrap justify-content-between flex-md-row flex-column text-center text-md-start">
          <div class="mb-2 mb-md-0">
              <span class="footer-text">©
                  <script>
                      document.write(new Date().getFullYear());
                  </script>
              </span>
              <a href="<?php echo e(config('variables.creatorUrl')); ?>" target="_blank" class="fw-medium text-white footer-link"><?php echo e(config('variables.templateName')); ?></a>
              <span class="footer-text"> | Versão <?php echo e(env('APP_VERSION', '1.0.0')); ?></span>
          </div>
      </div>
  </div>
</footer>
<!-- Footer: End -->
<?php /**PATH /www/wwwroot/gestor.veetv.fun/resources/views/layouts/sections/footer/footer-front.blade.php ENDPATH**/ ?>