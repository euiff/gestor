@extends('layouts/layoutMaster')

@section('title', 'Preview - Invoice')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
@endsection

@section('page-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/app-invoice.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/moment/locale/pt-br.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/l10n/pt.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave-phone.js') }}"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/offcanvas-add-payment.js') }}"></script>
    <script src="{{ asset('assets/js/offcanvas-send-invoice.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar Flatpickr para usar o idioma pt
            flatpickr.localize(flatpickr.l10ns.pt);
            flatpickr(".flatpickr", {
                dateFormat: "d M, Y"
            });

            // Configurar Moment.js para usar o idioma pt-br
            moment.locale('pt-br');
        });
    </script>
    <script>
        function showLoadingModal() {
          var loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
          loadingModal.show();
        }
      </script>
@endsection

@section('content')

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif
        <style>
        .bg-label-warning {
    background-color: #7367f0 !important;
    color: white!important;
    }
    </style>
    <div class="row invoice-preview">
               <!-- Invoice -->
        <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-4">
            <div class="card invoice-preview-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column m-sm-3 m-0">
                        <div class="mb-xl-0 mb-4">
                            <div class="d-flex svg-illustration mb-4 gap-2 align-items-center">
                                @include('_partials.macros', ['height' => 20, 'withbg' => ''])
                                <span class="app-brand-text fw-bold fs-4">
                                    {{ $empresa->company_name }}
                                </span>
                            </div>
                            <p class="mb-2">WhatsApp da Empresa:</p>
                            <p class="mb-2">{{ $empresa->company_whatsapp }}</p>
                        </div>
                        <div>
                            @php
                            use Carbon\Carbon;
                            Carbon::setLocale('pt_BR');
                            @endphp
                            <h4 class="fw-medium mb-2">Cobrança #{{ $payment->id }}</h4>
                            <div class="mb-2 pt-1">
                                <span>Data de Emissão:</span>
                                <span class="fw-medium">
                                    {{ $payment->created_at ? $payment->created_at->translatedFormat('d/m/Y') : 'N/A' }}
                                </span>
                            </div>
                            <div class="pt-1">
                                <span>Data de Vencimento:</span>
                                <span class="fw-medium">
                                    {{ $cliente->vencimento ? \Carbon\Carbon::parse($cliente->vencimento)->format('d/m/Y') : 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="my-0" />
                <div class="card-body">
                    <div class="row p-sm-3 p-0">
                        <div class="col-xl-6 col-md-12 col-sm-5 col-12 mb-xl-0 mb-md-4 mb-sm-0 mb-4">
                            <h6 class="mb-3">Cobrança Para:</h6>
                            <p class="mb-1">{{ $cliente->nome }}</p>
                            <p class="mb-1">{{ $cliente->whatsapp }}</p>
                        </div>
                        <div class="col-xl-6 col-md-12 col-sm-7 col-12">
                            <h6 class="mb-4">Plano:</h6>
                            <p class="mb-1">{{ $plano->nome }}</p>
                            <p class="mb-1">Preço: R${{ $plano->preco }}</p>
                            <p class="mb-1">Duração: {{ $plano->duracao }} dias</p>
                        </div>
                    </div>
                </div>
                <div class="table-responsive border-top">
                    <table class="table m-0">
                        <thead>
                            <tr>
                                <th>ID do Pagamento</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Data de Criação</th>
                                <th>Data de Pagamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td>R${{ $payment->valor }}</td>
                                <td>
                                    @if ($payment->status == 'approved')
                                        <span class="badge bg-label-success">Aprovado</span>
                                    @elseif($payment->status == 'pending')
                                        <span class="badge bg-label-warning">Pendente</span>
                                    @elseif($payment->status == 'cancelled')
                                        <span class="badge bg-label-danger">Cancelado</span>
                                    @endif
                                </td>
                                <td>{{ $payment->created_at ? $payment->created_at->translatedFormat('d/m/Y') : 'N/A' }}</td>
                                <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : 'Aguardando Pagamento' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
        
                @if(!empty($cliente->notas))
                    <div class="card-body mx-3">
                        <div class="row">
                            <div class="col-12">
                                <span class="fw-medium">Nota:</span>
                                <span>{{ $cliente->notas }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <!-- /Invoice -->

        <!-- Invoice Actions -->
        <div class="col-xl-3 col-md-4 col-12 invoice-actions">
            <div class="card">
                <div class="card-body">
                    <!-- <button class="btn btn-primary d-grid w-100 mb-2" data-bs-toggle="offcanvas"
                  data-bs-target="#sendInvoiceOffcanvas">
                  <span class="d-flex align-items-center justify-content-center text-nowrap"><i
                      class="ti ti-send ti-xs me-2"></i>Enviar Cobrança</span>
                </button> -->

                    <a class="btn btn-label-secondary d-grid w-100 mb-2" target="_blank"
                        href="{{ url('app/invoice/print', ['payment_id' => $payment->id]) }}">
                        Imprimir
                    </a>
                    <!-- <a href="{{ url('app/invoice/edit') }}" class="btn btn-label-secondary d-grid w-100 mb-2">
                  Editar Cobrança
                </a> -->
                    <button class="btn btn-primary d-grid w-100" data-bs-toggle="offcanvas"
                        data-bs-target="#addPaymentOffcanvas">
                        <span class="d-flex align-items-center justify-content-center text-nowrap"><i
                                class="ti ti-currency-dollar ti-xs me-2"></i>Adicionar Pagamento</span>
                    </button>
                </div>
            </div>
        </div>
        <!-- /Invoice Actions -->
    </div>

    <!-- Offcanvas -->
    @include('_partials/_offcanvas/offcanvas-send-invoice')
    @include('_partials/_offcanvas/offcanvas-add-payment')
    <!-- /Offcanvas -->
@endsection
