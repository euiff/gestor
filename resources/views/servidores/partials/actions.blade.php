<div class="btn-group" role="group" aria-label="Ações">
    <a href="{{ route('servidores.edit', $servidor->id) }}" class="btn btn-sm btn-primary">Editar</a>
    <form action="{{ route('servidores.destroy', $servidor->id) }}" method="POST" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
    </form>
</div>