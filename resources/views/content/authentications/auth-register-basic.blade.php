
@extends('layouts/layoutMaster')

@section('title', 'Registro Básico - Páginas')

@section('vendor-style')
<!-- Vendor -->
<link rel="stylesheet" href="{{asset('assets/vendor/libs/@form-validation/umd/styles/index.min.css')}}" />
@endsection

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/page-auth.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/@form-validation/umd/bundle/popular.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/@form-validation/umd/plugin-bootstrap5/index.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/@form-validation/umd/plugin-auto-focus/index.min.js')}}"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/pages-auth.js')}}"></script>
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">

      <!-- Register Card -->
      <div class="card">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center mb-4 mt-2">
            <a href="{{url('/')}}" class="app-brand-link gap-2">
              <span class="app-brand-logo demo">@include('_partials.macros',["height"=>20,"withbg"=>'fill: #fff;'])</span>
              <span class="app-brand-text demo text-body fw-bold ms-1">{{config('variables.templateName')}}</span>
            </a>
          </div>
          <!-- /Logo -->
          <h4 class="mb-1 pt-2">A aventura começa aqui 🚀</h4>
          <p class="mb-4">Torne a gestão do seu aplicativo fácil e divertida!</p>

          <form id="formAuthentication" class="mb-3" action="{{ route('auth-register-basic-post') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="name" class="form-label">Nome de Usuário</label>
        <input type="text" class="form-control" id="name" name="name" placeholder="Digite seu nome de usuário" autofocus>
        @if ($errors->has('name'))
            <span class="text-danger">{{ $errors->first('name') }}</span>
        @endif
    </div>
    <div class="mb-3">
        <label for="whatsapp" class="form-label">Número do WhatsApp</label>
        <input type="text" class="form-control" id="whatsapp" name="whatsapp" maxlength="15" placeholder="Digite seu número do WhatsApp" oninput="masktel(this)">
        @if ($errors->has('whatsapp'))
            <span class="text-danger">{{ $errors->first('whatsapp') }}</span>
        @endif
    </div>
    <div class="mb-3 form-password-toggle">
        <label class="form-label" for="password">Senha</label>
        <div class="input-group input-group-merge">
            <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" />
            <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
        </div>
        @if ($errors->has('password'))
            <span class="text-danger">{{ $errors->first('password') }}</span>
        @endif
    </div>

    <div class="mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="terms-conditions" name="terms">
            <label class="form-check-label" for="terms-conditions">
                Eu concordo com a
                <a href="javascript:void(0);">política de privacidade & termos</a>
            </label>
        </div>
        @if ($errors->has('terms'))
            <span class="text-danger">{{ $errors->first('terms') }}</span>
        @endif
    </div>

    <!-- Campo oculto para o ID de referência -->
    <input type="hidden" name="ref" value="{{ request()->get('ref') }}">

    <button class="btn btn-primary d-grid w-100">
        Registrar
    </button>
</form>

<script>
    // Função de máscara para telefone
    function masktel(input) {
        let v = input.value.replace(/\D/g, ""); // Remove tudo que não é dígito
        v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); // Coloca parênteses em volta dos dois primeiros dígitos
        v = v.replace(/(\d)(\d{4})$/, "$1-$2"); // Coloca hífen antes dos últimos 4 dígitos
        input.value = v; // Atualiza o valor do campo com a máscara aplicada
    }
</script>

          <p class="text-center">
            <span>Já tem uma conta?</span>
            <a href="{{url('auth/login-basic')}}">
              <span>Entrar</span>
            </a>
          </p>
        </div>
      </div>
      <!-- Register Card -->
    </div>
  </div>
</div>
@endsection
