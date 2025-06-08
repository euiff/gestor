
@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

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
<script src="{{asset('assets/js/app-logistics-dashboard.js')}}"></script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Clientes /</span> Dashboard
</h4>

<!-- Card Border Shadow -->
<div class="row">
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2 pb-1">
          <div class="avatar me-2">
            <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-shopping-cart ti-md"></i></span>
          </div>
          <h4 class="ms-1 mb-0">{{ $totalCompras }}</h4>
        </div>
        <p class="mb-1">Total de Compras</p>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-warning">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2 pb-1">
          <div class="avatar me-2">
            <span class="avatar-initial rounded bg-label-warning"><i class='ti ti-alert-triangle ti-md'></i></span>
          </div>
          <h4 class="ms-1 mb-0">{{ $comprasPendentes }}</h4>
        </div>
        <p class="mb-1">Compras Pendentes</p>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-danger">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2 pb-1">
          <div class="avatar me-2">
            <span class="avatar-initial rounded bg-label-danger"><i class='ti ti-git-fork ti-md'></i></span>
          </div>
          <h4 class="ms-1 mb-0">{{ $comprasCanceladas }}</h4>
        </div>
        <p class="mb-1">Compras Canceladas</p>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-info">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2 pb-1">
          <div class="avatar me-2">
            <span class="avatar-initial rounded bg-label-info"><i class='ti ti-clock ti-md'></i></span>
          </div>
          <h4 class="ms-1 mb-0">{{ $comprasAtrasadas }}</h4>
        </div>
        <p class="mb-1">Compras Aprovadas</p>
      </div>
    </div>
  </div>
</div>

<!-- Detalhes da Assinatura do Cliente -->
<div class="row">
  @if($cliente)
    <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card card-border-shadow-primary">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-primary"><i class='ti ti-user ti-md'></i></span>
            </div>
            <h4 class="ms-1 mb-0">{{ $cliente->nome }}</h4>
          </div>
          <p class="mb-1">Nome</p>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card card-border-shadow-secondary">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-secondary"><i class='ti ti-phone ti-md'></i></span>
            </div>
            <h4 class="ms-1 mb-0">{{ $cliente->whatsapp }}</h4>
          </div>
          <p class="mb-1">WhatsApp</p>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card card-border-shadow-success">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-success"><i class='ti ti-calendar ti-md'></i></span>
            </div>
            <h4 class="ms-1 mb-0">{{ \Carbon\Carbon::parse($cliente->vencimento)->format('d/m/Y') }}</h4>
          </div>
          <p class="mb-1">Vencimento</p>
        </div>
      </div>
    </div>
        <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card card-border-shadow-danger">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-danger"><i class='ti ti-package ti-md'></i></span>
            </div>
            <h4 class="ms-1 mb-0">{{ $cliente->plano->nome }}</h4>
          </div>
          <p class="mb-1">Plano</p>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card card-border-shadow-warning">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-warning"><i class='ti ti-quote ti-md'></i></span>
            </div>
            <h4 class="ms-1 mb-0">{{ $cliente->numero_de_telas }}</h4>
          </div>
          <p class="mb-1">Número de Telas</p>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card card-border-shadow-info">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-info"><i class='ti ti-notes ti-md'></i></span>
            </div>
            <h4 class="ms-1 mb-0">{{ $cliente->notas }}</h4>
          </div>
          <p class="mb-1">Notas</p>
        </div>
      </div>
    </div>
  @else
    <div class="col-12">
      <div class="card card-border-shadow-info">
        <div class="card-body">
          <p>Detalhes do cliente não encontrados.</p>
        </div>
      </div>
    </div>
  @endif
</div>

@endsection
