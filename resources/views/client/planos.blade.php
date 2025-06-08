@extends('layouts/layoutMaster')

@section('title', 'Meus Planos - Cliente')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}" />
@endsection

@section('page-style')
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/app-logistics-dashboard.css')}}" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
@endsection

@section('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var planosModal = new bootstrap.Modal(document.getElementById('planosModalUniquePlanos'));
    planosModal.show();
  });

  function openPixPaymentModalPlanos(planoId) {
    document.getElementById('planoIdUniquePlanos').value = planoId;
    var pixPaymentModal = new bootstrap.Modal(document.getElementById('pixPaymentModalUniquePlanos'));
    pixPaymentModal.show();
  }

  document.getElementById('pixPaymentFormUniquePlanos').addEventListener('submit', async function(event) {
      event.preventDefault();
      const planoId = document.getElementById('planoIdUniquePlanos').value;
      const userId = document.getElementById('userIdUniquePlanos').value;

      try {
        const response = await fetch(`{{ route('process-payment-planos', ['clienteId' => Auth::user()->id]) }}`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            plano_id: planoId,
            cliente_id: userId
          })
        });

        const data = await response.json();

        if (data.success) {
          document.getElementById('pix-section-uniquePlanos').classList.remove('d-none');
          document.getElementById('pix-qrcode-uniquePlanos').innerText = data.payload_pix;
          var pixQrcodeBase = document.getElementById('pix-qrcodeBase-uniquePlanos');
          if (pixQrcodeBase) {
            pixQrcodeBase.src = 'data:image/png;base64,' + data.qr_code_base64;
          }
          document.getElementById('copy-pix-code-uniquePlanos').classList.remove('d-none');

          const paymentId = data.payment_id;
          const intervalId = setInterval(async () => {
            const status = await checkPaymentStatusPlanos(paymentId);
            if (status === 'approved') {
              clearInterval(intervalId);
              document.getElementById('paymentSuccessMessageUniquePlanos').classList.remove('d-none');
              document.getElementById('paymentSuccessMessageUniquePlanos').innerText = 'Pagamento aprovado com sucesso.';
              var pixPaymentModal = bootstrap.Modal.getInstance(document.getElementById('pixPaymentModalUniquePlanos'));
              pixPaymentModal.hide();
            } else if (status === 'cancelled') {
              clearInterval(intervalId);
              alert('Pagamento cancelado.');
            }
          }, 5000);
        } else {
          alert('Erro ao processar o pagamento: ' + data.message);
        }
      } catch (error) {
        console.error('Erro ao processar o pagamento:', error);
        alert('Erro ao processar o pagamento: ' + error.message);
      }
    });

    async function checkPaymentStatusPlanos(paymentId) {
      try {
        const response = await fetch(`/api/payment-status/${paymentId}`);
        const data = await response.json();
        if (data.success) {
          return data.status;
        } else {
          console.error('Erro ao verificar status do pagamento:', data.message);
          return null;
        }
      } catch (error) {
        console.error('Erro ao verificar status do pagamento:', error);
        return null;
      }
    }

    document.getElementById('copy-pix-code-uniquePlanos').addEventListener('click', function () {
      var pixCodeElement = document.getElementById('pix-qrcode-uniquePlanos');
      var range = document.createRange();
      range.selectNode(pixCodeElement);
      window.getSelection().removeAllRanges();
      window.getSelection().addRange(range);
      try {
        document.execCommand('copy');
        alert('Código PIX copiado para a área de transferência!');
      } catch (err) {
        alert('Erro ao copiar o código PIX.');
      }
      window.getSelection().removeAllRanges();
    });
</script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Clientes /</span> Meus Planos
</h4>

<!-- Pricing Modal -->
<div class="modal fade" id="planosModalUniquePlanos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-simple modal-pricing">
    <div class="modal-content p-2 p-md-5">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <!-- Pricing Plans -->
        <div class="pb-sm-5 pb-2 rounded-top">
          <h2 class="text-center mb-2">Planos Disponíveis</h2>
          <p class="text-center">Escolha um plano para continuar gerenciando seus clientes Veetv.</p>
          <div class="row mx-0 gy-3">
            @foreach($planos as $plano)
              <div class="col-xl mb-md-0 mb-4">
                <div class="card border border rounded shadow-none">
                  <div class="card-body">
                    <div class="my-3 pt-2 text-center">
                      <img src="{{ asset('assets/img/illustrations/' . ($loop->first ? 'page-pricing-basic.png' : ($loop->iteration == 2 ? 'page-pricing-standard.png' : 'page-pricing-enterprise.png'))) }}" alt="Image" height="140">
                    </div>
                    <h3 class="card-title text-center text-capitalize mb-1">{{ $plano->nome }}</h3>
                    <p class="text-center">Duração: {{ $plano->duracao }} dias</p>
                    <p class="text-center">Preço: R$ {{ number_format($plano->preco, 2, ',', '.') }}</p>
                    <div class="text-center h-px-100">
                      <div class="d-flex justify-content-center">
                        <sup class="h6 pricing-currency mt-3 mb-0 me-1 text-primary">R$</sup>
                        <h1 class="display-4 mb-0 text-primary">{{ number_format($plano->preco, 2, ',', '.') }}</h1>
                        <sub class="h6 pricing-duration mt-auto mb-2 text-muted fw-normal">/Plano</sub>
                      </div>
                    </div>
                    <form id="planoForm{{ $plano->id }}" action="{{ route('process-payment-planos', ['clienteId' => Auth::user()->id]) }}" method="POST">
                      @csrf
                      <input type="hidden" name="plano_id" value="{{ $plano->id }}">
                      <input type="hidden" name="cliente_id" value="{{ Auth::user()->id }}">
                      <button type="button" class="btn btn-label-success d-grid w-100 mt-3" onclick="openPixPaymentModalPlanos({{ $plano->id }})">Comprar</button>
                    </form>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
        <!--/ Pricing Plans -->
      </div>
    </div>
  </div>
</div>
<!--/ Pricing Modal -->

<!-- Modal para Selecionar Opção de Pagamento PIX -->
<div class="modal fade" id="pixPaymentModalUniquePlanos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-simple modal-add-new-address">
    <div class="modal-content p-3 p-md-5">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-4">
          <h3 class="address-title mb-2">Pagamento com PIX</h3>
          <p class="text-muted address-subtitle">Escolha o método de pagamento</p>
        </div>
        <form id="pixPaymentFormUniquePlanos" class="row g-3" onsubmit="return false">
          @if (Auth::check())
            <input type="hidden" id="userIdUniquePlanos" value="{{ Auth::user()->id }}">
          @endif
          <input type="hidden" id="planoIdUniquePlanos" value="">
          <div class="col-12">
            <div class="form-check custom-option custom-option-icon">
              <input class="form-check-input" type="radio" name="paymentMethod" id="pixPaymentUniquePlanos" value="pix" checked>
              <label class="form-check-label" for="pixPaymentUniquePlanos">
                <span class="option-icon"><i class="bx bxs-credit-card"></i></span>
                <span class="option-title">PIX</span>
              </label>
            </div>
          </div>
          <div id="pix-section-uniquePlanos" class="d-none">
            <div class="alert alert-info" role="alert">
              <p id="pix-code-uniquePlanos" class="mb-2"></p>
              <img id="pix-qrcodeBase-uniquePlanos" src="" alt="QR Code PIX" class="img-fluid d-block mx-auto" style="max-width: 200px;" />
              <br>
              <pre id="pix-qrcode-uniquePlanos" class="text-break" style="word-wrap: break-word; white-space: pre-wrap; background-color: #f8f9fa; padding: 10px; border-radius: 5px;"></pre>
              <button type="button" id="copy-pix-code-uniquePlanos" class="btn btn-primary d-block mx-auto">Copiar Código PIX</button>
            </div>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">Pagar</button>
          </div>
        </form>
        <div id="paymentSuccessMessageUniquePlanos" class="alert alert-success d-none mt-3" role="alert">
          Pagamento realizado com sucesso.
        </div>
      </div>
    </div>
  </div>
</div>
<!--/ Modal para Selecionar Opção de Pagamento PIX -->

@endsection
