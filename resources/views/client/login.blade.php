@extends('layouts/layoutMaster')

@section('title', 'Login do Cliente')

@section('vendor-style')
<!-- Vendor -->
<link rel="stylesheet" href="{{asset('assets/vendor/libs/@form-validation/umd/styles/index.min.css')}}" />
@endsection

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/page-auth.css')}}">
@endsection

@section('vendor-script')
<!-- Adicionando o Inputmask via CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.6/jquery.inputmask.min.js"></script>
<script src="{{asset('assets/vendor/libs/@form-validation/umd/plugin-bootstrap5/index.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/@form-validation/umd/plugin-auto-focus/index.min.js')}}"></script>
@endsection

@section('page-script')
<script>
function mask(o, f) {
    v_obj = o;
    v_fun = f;
    setTimeout(function() { execmask(); }, 1);
}

function execmask() {
    v_obj.value = v_fun(v_obj.value);
}

function masktel(v) 
{
    v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); // Coloca parênteses em volta dos dois primeiros dígitos
    v = v.replace(/(\d)(\d{4})$/, "$1-$2"); // Coloca hífen antes dos últimos 4 dígitos
    return v;
}

// Função auxiliar para selecionar elementos por ID
function idcss(el) {
    return document.getElementById(el);
}

// Aplicar máscara ao campo de WhatsApp
document.addEventListener('DOMContentLoaded', function() {
    const whatsappInput = idcss('whatsapp');
    if (whatsappInput) {
        whatsappInput.setAttribute('maxlength', 15); // Define o comprimento máximo
        whatsappInput.oninput = function () {
            mask(this, masktel); // Aplica a máscara dinamicamente
        };
    }

  const form = document.getElementById('formAuthentication');

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    // Limpar mensagens de erro anteriores
    const errorMessages = document.querySelectorAll('.text-danger');
    errorMessages.forEach(function (message) {
      message.remove();
    });

    let isValid = true;

    // Validação do número de WhatsApp
    const whatsappValue = whatsappInput.value.trim();
    const whatsappErrorContainer = whatsappInput.nextElementSibling;
    if (whatsappValue === '') {
      isValid = false;
      const errorMessage = document.createElement('span');
      errorMessage.classList.add('text-danger');
      errorMessage.textContent = 'Por favor, insira seu número de WhatsApp';
      whatsappErrorContainer.appendChild(errorMessage);
    }

    // Validação da senha
    const passwordInput = document.getElementById('password');
    const passwordValue = passwordInput.value.trim();
    const passwordErrorContainer = passwordInput.parentNode.parentNode.querySelector('.error-container');
    if (passwordValue === '') {
      isValid = false;
      const errorMessage = document.createElement('span');
      errorMessage.classList.add('text-danger');
      errorMessage.textContent = 'Por favor, insira sua senha';
      passwordErrorContainer.appendChild(errorMessage);
    } else if (passwordValue.length < 6) {
      isValid = false;
      const errorMessage = document.createElement('span');
      errorMessage.classList.add('text-danger');
      errorMessage.textContent = 'A senha deve ter pelo menos 6 caracteres';
      passwordErrorContainer.appendChild(errorMessage);
    }

    if (isValid) {
      // Remover a máscara antes de enviar o formulário
      whatsappInput.value = whatsappValue.replace();
      form.submit();
    }
  });
});
</script>
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">
      <!-- Login -->
      <div class="card">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center mb-4 mt-2">
            <a href="{{url('/')}}" class="app-brand-link gap-2">
              <span class="app-brand-text demo text-body fw-bold ms-1">{{config('variables.templateName')}}</span>
            </a>
          </div>
          <!-- /Logo -->
          <h4 class="mb-1 pt-2">Bem-vindo ao {{config('variables.templateName')}}! 👋</h4>
          <p class="mb-4">Por favor, faça login na sua conta usando seu número de Whatsapp</p>

          <form id="formAuthentication" class="mb-3" action="{{ route('client.login') }}" method="POST">
            @csrf
            <div class="mb-3">
              <label for="whatsapp" class="form-label">Número de WhatsApp</label>
              <input type="text" class="form-control" id="whatsapp" name="whatsapp" placeholder="Digite seu número de WhatsApp" autofocus>
              <div class="error-container"></div>
              @if ($errors->has('whatsapp'))
                <span class="text-danger">{{ $errors->first('whatsapp') }}</span>
              @endif
            </div>
            <div class="mb-3 form-password-toggle">
              <div class="d-flex justify-content-between">
                <label class="form-label" for="password">Senha</label>
                <a href="{{url('auth/forgot-password-basic')}}">
                  <small>Esqueceu a senha?</small>
                </a>
              </div>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" />
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
              </div>
              <div class="error-container"></div>
              @if ($errors->has('password'))
                <span class="text-danger">{{ $errors->first('password') }}</span>
              @endif
            </div>
            <div class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember-me" name="remember">
                <label class="form-check-label" for="remember-me">
                  Lembrar-me
                </label>
              </div>
            </div>
            <div class="mb-3">
              <button class="btn btn-primary d-grid w-100" type="submit">Entrar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection