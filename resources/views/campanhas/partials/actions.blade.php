<div class="d-flex gap-2">
    <!-- Botão de Excluir -->
    <form action="{{ route('campanhas.destroy', $campanha->id) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta campanha?')">
            <i class="fas fa-trash-alt"></i>
        </button>
    </form>
</div>