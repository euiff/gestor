<div class="d-grid gap-3">
    <div class="row g-3">
        <div class="col-6 mb-2">
            <form action="{{ route('app-ecommerce-customer-destroy', $cliente->id) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Deletar">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </form>
        </div>
        <div class="col-6 mb-2">
            <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#editClient{{ $cliente->id }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                <i class="fas fa-edit"></i>
            </button>
        </div>
        <div class="col-6 mt-2">
            <form action="{{ route('app-ecommerce-customer-charge', $cliente->id) }}" method="POST" style="display:inline;">
                @csrf
                @method('POST')
                <button type="submit" class="btn btn-sm btn-warning w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Cobrança Manual">
                    <i class="fas fa-dollar-sign"></i>
                </button>
            </form>
        </div>
        <div class="col-6 mt-2">
            <a href="{{ route('app-ecommerce-order-list', ['order_id' => $cliente->id]) }}" class="btn btn-sm btn-success w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Detalhes da Cobrança">
                <i class="fas fa-thumbs-up"></i>
            </a>
        </div>
        <div class="col-6 mt-2">
            <button class="btn btn-sm btn-info w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Enviar Dados de Login" onclick="sendLoginDetails('{{ $cliente->id }}')">
                <i class="fab fa-whatsapp"></i>
            </button>
        </div>
    </div>
</div>