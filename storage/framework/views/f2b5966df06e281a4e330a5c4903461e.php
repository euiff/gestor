<div class="d-flex gap-2">
    <!-- Botão de Excluir -->
    <form action="<?php echo e(route('campanhas.destroy', $campanha->id)); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <?php echo method_field('DELETE'); ?>
        <button type="submit" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta campanha?')">
            <i class="fas fa-trash-alt"></i>
        </button>
    </form>
</div><?php /**PATH /www/wwwroot/developer.veetv.fun/resources/views/campanhas/partials/actions.blade.php ENDPATH**/ ?>