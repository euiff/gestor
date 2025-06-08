<?php
    $configData = Helper::appClasses();
?>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    <?php if(!isset($navbarFull)): ?>
        <div class="app-brand demo">
            <a href="<?php echo e(url('/app/ecommerce/dashboard')); ?>" class="app-brand-link">
                <span class="app-brand-logo demo">
                    <?php echo $__env->make('_partials.macros', ['height' => 20], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </span>
                <span class="app-brand-text demo menu-text fw-bold"><?php echo e(config('variables.templateName')); ?></span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
                <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
            </a>
        </div>
    <?php endif; ?>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <?php $__currentLoopData = $menuData[0]->menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(
                (isset($menu->adminOnly) && Auth::check() && Auth::user()->role_id == 1) ||
                    (isset($menu->masterOnly) && Auth::check() && Auth::user()->role_id == 2) ||
                    (isset($menu->revededorOnly) && Auth::check() && Auth::user()->role_id == 4) ||
                    (isset($menu->clientOnly) &&
                        Auth::guard('cliente')->check() &&
                        Auth::guard('cliente')->user()->role_id == 3 &&
                        $menu->clientOnly) ||
                    (!isset($menu->adminOnly) &&
                        !isset($menu->masterOnly) &&
                        !isset($menu->revededorOnly) &&
                        !isset($menu->clientOnly))): ?>
                <?php if(isset($menu->menuHeader)): ?>
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text"><?php echo e(__($menu->menuHeader)); ?></span>
                    </li>
                <?php else: ?>
                    <?php
                        $activeClass = null;
                        $currentRouteName = Route::currentRouteName();

                        if (isset($menu->slug) && $currentRouteName === $menu->slug) {
                            $activeClass = 'active';
                        } elseif (isset($menu->submenu)) {
                            if (is_array($menu->slug)) {
                                foreach ($menu->slug as $slug) {
                                    if (
                                        str_contains($currentRouteName, $slug) &&
                                        strpos($currentRouteName, $slug) === 0
                                    ) {
                                        $activeClass = 'active open';
                                    }
                                }
                            } else {
                                if (
                                    str_contains($currentRouteName, $menu->slug) &&
                                    strpos($currentRouteName, $menu->slug) === 0
                                ) {
                                    $activeClass = 'active open';
                                }
                            }
                        }
                    ?>

                    <li class="menu-item <?php echo e($activeClass); ?>">
                        <a href="<?php echo e(isset($menu->url) ? url($menu->url) : 'javascript:void(0);'); ?>"
                            class="<?php echo e(isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link'); ?>"
                            <?php if(isset($menu->target) && !empty($menu->target)): ?> target="_blank" <?php endif; ?>>
                            <?php if(isset($menu->icon)): ?>
                                <i class="<?php echo e($menu->icon); ?>"></i>
                            <?php endif; ?>
                            <div><?php echo e(isset($menu->name) ? __($menu->name) : ''); ?></div>
                            <?php if(isset($menu->badge)): ?>
                                <?php
                                    $badgeText = is_object($menu->badge) ? ($menu->badge->text ?? 'New') : (is_array($menu->badge) ? ($menu->badge[1] ?? 'New') : 'New');
                                    $badgeClass = is_object($menu->badge) ? ($menu->badge->classes ?? 'badge bg-primary rounded-pill ms-auto') : 'badge bg-'.(is_array($menu->badge) ? ($menu->badge[0] ?? 'primary') : 'primary').' rounded-pill ms-auto';
                                ?>
                                <div class="<?php echo e($badgeClass); ?>"><?php echo e($badgeText); ?></div>
                            <?php endif; ?>
                        </a>

                        <?php if(isset($menu->submenu)): ?>
                            <?php echo $__env->make('layouts.sections.menu.submenu', ['menu' => $menu->submenu], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</aside><?php /**PATH /www/wwwroot/developer.veetv.fun/resources/views/layouts/sections/menu/verticalMenu.blade.php ENDPATH**/ ?>