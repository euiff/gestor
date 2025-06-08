
@extends('layouts/layoutMaster')

@section('title', 'Indicações')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/app-ecommerce-referral.js')}}"></script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
<span class="text-muted fw-light">{{ config('variables.templateName', 'TemplateName') }} / </span> Indicações
</h4>

<div class="row mb-4 g-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div class="content-left">
            <h4 class="mb-0">R${{ number_format($totalGanhos, 2, ',', '.') }}</h4>
            <small>Ganhos Totais</small>
          </div>
          <span class="badge bg-label-primary rounded-circle p-2">
            <i class="ti ti-currency-dollar ti-md"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div class="content-left">
            <h4 class="mb-0">R${{ number_format($ganhosNaoPagos, 2, ',', '.') }}</h4>
            <small>Ganhos Não Pagos</small>
          </div>
          <span class="badge bg-label-success rounded-circle p-2">
            <i class="ti ti-gift ti-md"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div class="content-left">
            <h4 class="mb-0">{{ $indicacoes->count() }}</h4>
            <small>Cadastros</small>
          </div>
          <span class="badge bg-label-danger rounded-circle p-2">
            <i class="ti ti-user ti-md"></i>
          </span>
        </div>
      </div>
    </div>
  </div>


<div class="row mb-4 g-4">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="mb-2">Como usar</h5>
        <p class="mb-4">Integre seu código de indicação em 3 passos fáceis.</p>
        <div class="d-flex flex-column flex-sm-row justify-content-between text-center gap-3">
          <div class="d-flex flex-column align-items-center">
            <span><i class='ti ti-rocket text-primary ti-xl p-3 border border-1 border-primary rounded-circle border-dashed mb-0'></i></span>
            <small class="my-2 w-75">Crie e valide seu link de indicação e ganhe</small>
            <h5 class="text-primary mb-0">R${{ number_format($referralBalance, 2, ',', '.') }}</h5>
          </div>
          <div class="d-flex flex-column align-items-center">
            <span><i class='ti ti-id text-primary ti-xl p-3 border border-1 border-primary rounded-circle border-dashed mb-0'></i></span>
            <small class="my-2 w-75">Para cada novo cadastro que contratar um plano você ganha</small>
            <h5 class="text-primary mb-0">R${{ number_format($referralBalance, 2, ',', '.') }}</h5>
          </div>
        </div>
      </div>
    </div>
  </div>

   <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-body">
          <form class="referral-form" onsubmit="submitReferral(event)">
            <div class="mb-4 mt-1">
            <h5>Convide seus amigos</h5>
<div class="d-flex flex-wrap gap-3 align-items-end">
  <div class="w-75">
    <label class="form-label mb-0" for="referralwhatsapp">Digite o número do WhatsApp do seu amigo e convide-o</label>
    <input type="text" id="referralwhatsapp" name="referralwhatsapp" class="form-control w-100" placeholder="Número do WhatsApp" />
  </div>
  <div>
    <button type="submit" class="btn btn-primary">Enviar</button>
  </div>
</div>
</div>
<div>
                            <!-- Adicione Cleave.js -->
              <!-- <script src="https://cdn.jsdelivr.net/npm/cleave.js@1.6.0/dist/cleave.min.js"></script>

              <script>
    document.addEventListener('DOMContentLoaded', function () {
      var cleave = new Cleave('#referralwhatsapp', {
        delimiters: [' ', ' ', '-'],
        blocks: [2, 5, 4],
        numericOnly: true,
        onValueChanged: function (e) {
          var value = e.target.rawValue;
          if (value.length > 2 && value[2] === '9') {
            value = value.slice(0, 2) + value.slice(3);
            cleave.setRawValue(value);
          }
        }
      });
    });
  </script> -->
            <h5>Compartilhe o link de indicação</h5>
            <div class="d-flex flex-wrap gap-3 align-items-end">
              <div class="w-75">
                <label class="form-label mb-0" for="referralLink">Compartilhe o link de indicação nas redes sociais</label>
                <input type="text" id="referralLink" name="referralLink" class="form-control w-100 h-px-40" value="{{ url('/auth/register-basic') }}?ref={{ Auth::id() }}" readonly />
              </div>
              <div>
                <button type="button" class="btn btn-facebook btn-icon me-2"><i class='ti ti-brand-facebook text-white ti-sm'></i></button>
                <button type="button" class="btn btn-twitter btn-icon"><i class='ti ti-brand-twitter text-white ti-sm'></i></button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Tabela de Indicações -->
<div class="card">
  <div class="card-datatable table-responsive">
    <table class="datatables-referral table border-top">
      <thead>
        <tr>
          <th>ID</th>
          <th>Usuário</th>
          <th>Indicado</th>
          <th>Status</th>
          <th>Data de Criação</th>
        </tr>
      </thead>
      <tbody>
      @foreach($indicacoes as $indicacao)
        <tr>
          <td>{{ $indicacao->id }}</td>
          <td>{{ $indicacao->user ? $indicacao->user->name : 'Usuário não encontrado' }}</td>
          <td>{{ $indicacao->referred ? $indicacao->referred->name : 'Indicado não encontrado' }}</td>
          <td>{{ ucfirst($indicacao->status) }}</td>
          <td>{{ $indicacao->created_at->format('d M, Y, H:i') }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<script>
function submitReferral(event) {
  event.preventDefault();
  const whatsapp = document.getElementById('referralwhatsapp').value;
  const userId = {{ Auth::id() }}; // Obtém o ID do usuário logado
  const message = `Olá! Cadastre-se usando meu link de indicação: {{ url('/auth/register-basic') }}?ref=${userId}`;

  fetch('{{ url('/send-message') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
      phone: whatsapp,
      message: message,
      user_id: userId,
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Mensagem enviada com sucesso:', data);
      alert('Mensagem enviada com sucesso!');
    } else {
      console.error('Erro ao enviar mensagem:', data);
      alert('Erro ao enviar mensagem.');
    }
  })
  .catch((error) => {
    console.error('Erro:', error);
    alert('Erro ao enviar mensagem.');
  });
}
</script>

@endsection
