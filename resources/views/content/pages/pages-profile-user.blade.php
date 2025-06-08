@extends('layouts/layoutMaster')

@section('title', 'Perfil do Usuário - Perfil')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css')}}">
@endsection

<!-- Page -->
@section('page-style')
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/page-profile.css')}}" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/pages-profile.js')}}"></script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Perfil do Usuário /</span> Perfil
</h4>

<!-- Header -->
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="user-profile-header-banner">
        <img src="{{ asset('assets/img/pages/profile-banner.png') }}" alt="Banner image" class="rounded-top">
      </div>
      <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-4">
        <div class="flex-shrink-0 mt-n2 mx-sm-0 mx-auto">
          <img src="{{ Auth::user()->profile_photo_url }}" alt="user image" class="d-block h-auto ms-0 ms-sm-4 rounded user-profile-img">
        </div>
        <div class="flex-grow-1 mt-3 mt-sm-5">
          <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-4 flex-md-row flex-column gap-4">
            <div class="user-profile-info">
              <h4>{{ Auth::user()->name }}</h4>
              <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-2">
                <li class="list-inline-item d-flex gap-1">
                  <i class='ti ti-color-swatch'></i> {{ Auth::user()->role->name }}
                </li>

                <li class="list-inline-item d-flex gap-1">
                @php
                  \Carbon\Carbon::setLocale('pt_BR');
                  @endphp
                  <i class='ti ti-calendar'></i> Entrou em {{ \Carbon\Carbon::parse(Auth::user()->created_at)->translatedFormat('F Y') }}
                </li>
                <li class="list-inline-item d-flex gap-1">
                  <i class='ti ti-user'></i> Status: {{ Auth::user()->status == 'ativo' ? 'Ativo' : 'Desativado' }}
                </li>
                @if (Auth::user()->role_id != 1)
                <li class="list-inline-item d-flex gap-1">
                  <i class='ti ti-clock'></i>
                  @php
                    $trialEndsAt = \Carbon\Carbon::parse(Auth::user()->trial_ends_at);
                    $daysRemaining = $trialEndsAt->diffInDays(\Carbon\Carbon::now());
                  @endphp
                  Expira em: {{ $daysRemaining > 0 ? $daysRemaining . ' dias' : $trialEndsAt->format('d/m/Y') }}
                </li>
                @endif
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!--/ Header -->

<!-- User Profile Content -->
<div class="row">
  <div class="col-xl-4 col-lg-5 col-md-5">
    <!-- About User -->
    <div class="card mb-4">
      <div class="card-body">
        <small class="card-text text-uppercase">Sobre</small>
        <ul class="list-unstyled mb-4 mt-3">
          <li class="d-flex align-items-center mb-3"><i class="ti ti-user text-heading"></i><span class="fw-medium mx-2 text-heading">Nome Completo:</span> <span>{{ Auth::user()->name }}</span></li>
          <li class="d-flex align-items-center mb-3"><i class="ti ti-check text-heading"></i><span class="fw-medium mx-2 text-heading">Status:</span> <span>Ativo</span></li>
          <li class="d-flex align-items-center mb-3"><i class="ti ti-crown text-heading"></i><span class="fw-medium mx-2 text-heading">Função:</span> <span>{{ Auth::user()->role->name }}</span></li>
        </ul>
        <small class="card-text text-uppercase">Contatos</small>
        <ul class="list-unstyled mb-4 mt-3">
          <li class="d-flex align-items-center mb-3"><i class="ti ti-phone-call"></i><span class="fw-medium mx-2 text-heading">Contato:</span> <span>{{ Auth::user()->whatsapp }}</span></li>
        </ul>

      </div>
    </div>
    <!--/ About User -->
  </div>
  <div class="col-xl-8 col-lg-7 col-md-7">
    <!-- Edit Profile Form -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">Editar Perfil</h5>
        <form action="{{ route('pages-profile-user-post') }}" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label for="name" class="form-label">Nome</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ Auth::user()->name }}" required>
          </div>

          <div class="mb-3">
            <label for="whatsapp" class="form-label">WhatsApp</label>
            <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="{{ Auth::user()->whatsapp }}" required>
          </div>

          <div class="mb-3">
            <label for="profile_photo" class="form-label">Foto de Perfil</label>
            <input type="file" class="form-control" id="profile_photo" name="profile_photo">
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Senha</label>
            <input type="password" class="form-control" id="password" name="password">
          </div>

          <div class="mb-3">
            <label for="two_factor" class="form-label">Autenticação de Dois Fatores</label>
            <select class="form-control" id="two_factor" name="two_factor">
              <option value="0" {{ Auth::user()->two_factor_secret ? '' : 'selected' }}>Desativado</option>
              <option value="1" {{ Auth::user()->two_factor_secret ? 'selected' : '' }}>Ativado</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary">Atualizar Perfil</button>
        </form>
      </div>
    </div>
    <!--/ Edit Profile Form -->
  </div>
</div>
<!--/ User Profile Content -->
@endsection