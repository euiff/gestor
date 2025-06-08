@extends('layouts/layoutMaster')

@section('title', 'Fatura (Versão para Impressão) - Páginas')

@section('page-style')
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/app-invoice-print.css')}}" />
@endsection

@section('page-script')
<script src="{{asset('assets/js/app-invoice-print.js')}}"></script>
@endsection

@section('content')
@php
  use Carbon\Carbon;
  Carbon::setLocale('pt_BR'); // Configurar o locale para português do Brasil
  $statusMap = [
    'pending' => 'Pendente',
    'approved' => 'Aprovado',
    'cancelled' => 'Cancelado',
  ];
@endphp

<div class="invoice-print p-5">
  <div class="d-flex justify-content-between flex-row">
    <div class="mb-4">
      <div class="d-flex svg-illustration mb-3 gap-2">
        @include('_partials.macros',["height"=>20,"withbg"=>''])
        <span class="app-brand-text fw-bold">
          {{ $empresa->company_name }}
        </span>
      </div>
      <p class="mb-1">WhatsApp da Empresa: {{ $empresa->company_whatsapp }}</p>
    </div>
    <div>
      <h4 class="fw-medium">COBRANÇA #{{ $payment->id }}</h4>
      <div class="mb-2">
        <span class="text-muted">Data de Emissão:</span>
        <span class="fw-medium">{{ $payment->created_at ? $payment->created_at->translatedFormat('d F, Y, H:i') : 'N/A' }}</span>
      </div>
      <div>
        <span class="text-muted">Data de Vencimento:</span>
        <span class="fw-medium">{{ $payment->due_date ? $payment->due_date->translatedFormat('d F, Y, H:i') : 'N/A' }}</span>
      </div>
    </div>
  </div>

  <hr />

  <div class="row d-flex justify-content-between mb-4">
    <div class="col-sm-6 w-50">
      <h6>Cobrança Para:</h6>
      <p class="mb-1">{{ $cliente->nome }}</p>
      <p class="mb-1">{{ $cliente->whatsapp }}</p>
    </div>
    <div class="col-sm-6 w-50">
      <h6>Plano:</h6>
      <p class="mb-1">{{ $plano->nome }}</p>
      <p class="mb-1">Preço: {{ $plano->preco }}</p>
      <p class="mb-1">Duração: {{ $plano->duracao }} dias</p>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table m-0">
      <thead class="table-light">
        <tr>
          <th>ID do Pagamento</th>
          <th>Valor</th>
          <th>Status</th>
          <th>Data de Criação</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>{{ $payment->id }}</td>
          <td>{{ $payment->valor }}</td>
          <td>
            @if(isset($statusMap[$payment->status]))
              <span class="badge bg-label-{{ $payment->status == 'approved' ? 'success' : ($payment->status == 'pending' ? 'warning' : 'danger') }}">
                {{ $statusMap[$payment->status] }}
              </span>
            @else
              <span class="badge bg-label-secondary">Desconhecido</span>
            @endif
          </td>
          <td>{{ $payment->created_at->translatedFormat('d F, Y, H:i') }}</td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="row">
    <div class="col-12">
      <span class="fw-medium">Nota:</span>
      <span>{{ $payment->note }}</span>
    </div>
  </div>
</div>
@endsection