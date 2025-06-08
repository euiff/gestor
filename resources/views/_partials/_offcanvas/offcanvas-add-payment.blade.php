<!-- Add Payment Sidebar -->
<div class="offcanvas offcanvas-end" id="addPaymentOffcanvas" aria-hidden="true">
  <div class="offcanvas-header mb-3">
    <h5 class="offcanvas-title">Adicionar Pagamento</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body flex-grow-1">
    <div class="d-flex justify-content-between bg-lighter p-2 mb-3">
      <p class="mb-0">Saldo da Fatura:</p>
      <p class="fw-medium mb-0">{{ number_format(abs($payment->total_due - $payment->valor), 2, ',', '.') }}</p>
    </div>
    <form action="{{ route('addPayment') }}" method="POST" onsubmit="showLoadingModal()">
      @csrf
      <input type="hidden" name="payment_id" value="{{ $payment->id }}">
      <div class="mb-3">
        <label class="form-label" for="invoiceAmount">Valor do Pagamento</label>
        <div class="input-group">
          <span class="input-group-text">R$</span>
          <input type="text" id="invoiceAmount" name="invoiceAmount" class="form-control invoice-amount" placeholder="0.00" />
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="payment-date">Data do Pagamento</label>
        <input id="payment-date" name="payment_date" class="form-control invoice-date" type="text" />
      </div>
      <div class="mb-3">
        <label class="form-label" for="payment-status">Status do Pagamento</label>
        <select class="form-select" id="payment-status" name="payment_status">
          <option value="" selected disabled>Selecione o status do pagamento</option>
          <option value="pending">Pendente</option>
          <option value="approved">Aprovado</option>
        </select>
      </div>

      <div class="mb-3 d-flex flex-wrap">
        <button type="submit" class="btn btn-primary me-3">Enviar</button>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">Cancelar</button>
      </div>
    </form>
  </div>
</div>
<!-- /Add Payment Sidebar -->

<!-- Modal de Carregamento -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Carregando...</span>
        </div>
        <p class="mt-3">Por favor, aguarde, atualizando os dados...</p>
      </div>
    </div>
  </div>
</div>