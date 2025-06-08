
@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'eCommerce Settings Payments - Apps')

@section('page-script')
<script src="{{asset('assets/js/app-ecommerce-settings.js')}}"></script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
<span class="text-muted fw-light">{{ config('variables.templateName', 'TemplateName') }} / </span> Modulos
</h4>

@if (session('success'))
<div class="alert alert-success alert-dismissible">
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  {{ session('success') }}
</div>
@endif

@if (session('error'))
<div class="alert alert-danger alert-dismissible">
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  {{ session('error') }}
</div>
@endif

<div class="row g-4">

  <!-- Navigation -->
  <div class="col-12 col-lg-4">
    <div class="d-flex justify-content-between flex-column mb-3 mb-md-0">
      <ul class="nav nav-align-left nav-pills flex-column">
        <li class="nav-item mb-1">
          <a class="nav-link py-2" href="{{url('/configuracoes')}}">
            <i class="ti ti-building-store me-2"></i>
            <span class="align-middle">Store details</span>
          </a>
        </li>
        <li class="nav-item mb-1">
          <a class="nav-link py-2" href="{{url('/modulos')}}">
            <i class="ti ti-package me-2"></i>
            <span class="align-middle">Modulos</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
  <!-- /Navigation -->

  <!-- Options -->
  <div class="col-12 col-lg-8 pt-4 pt-lg-0">
    <div class="tab-content p-0">
      <!-- Payments Tab -->
      <div class="tab-pane fade show active" id="payments" role="tabpanel">



        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title">Upload de Módulo</h5>
          </div>
          <div class="card-body">
            <form action="{{ route('modulo.upload') }}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="mb-3">
                <label for="modulo" class="form-label">Selecione o arquivo do módulo (.zip)</label>
                <input type="file" class="form-control" id="modulo" name="modulo" required>
              </div>
              <button type="submit" class="btn btn-primary">Upload e Instalar</button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
  <!-- /Options -->
</div>

@include('_partials/_modals/modal-select-payment-providers')
@include('_partials/_modals/modal-select-payment-methods')

@endsection
