@php
    $customizerHidden = 'customizer-hide';
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Login Cover - Pages')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/@form-validation/umd/styles/index.min.css') }}" />
@endsection

@section('page-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
@endsection
@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/@form-validation/umd/bundle/popular.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/@form-validation/umd/plugin-bootstrap5/index.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/@form-validation/umd/plugin-auto-focus/index.min.js') }}"></script>
@endsection
@section('page-script')
<script>
// Funções de máscara
function mask(o, f) {
    v_obj = o;
    v_fun = f;
    setTimeout(function() { execmask(); }, 1);
}

function execmask() {
    v_obj.value = v_fun(v_obj.value);
}

function masktel(v) {
    v = v.replace(/\D/g, ""); // Remove tudo que não é dígito
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
    const twoFactorForm = document.getElementById('twoFactorForm');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const digitInputs = document.querySelectorAll('#twoFactorForm input[type="text"]');

    digitInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            if (input.value.length === 1 && index < digitInputs.length - 1) {
                digitInputs[index + 1].focus();
            }
        });
        input.addEventListener('paste', (event) => {
            event.preventDefault();
            const pasteData = (event.clipboardData || window.clipboardData).getData('text');
            const pasteDigits = pasteData.split('');
            pasteDigits.forEach((digit, i) => {
                if (digitInputs[i]) {
                    digitInputs[i].value = digit;
                }
            });
            const nextInput = digitInputs[pasteDigits.length];
            if (nextInput) {
                nextInput.focus();
            }
        });
    });

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const errorMessages = document.querySelectorAll('.text-danger');
        errorMessages.forEach(function(message) {
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
        } else if (!/^\(\d{2}\) \d{4,5}-\d{4}$/.test(whatsappValue)) {
            isValid = false;
            const errorMessage = document.createElement('span');
            errorMessage.classList.add('text-danger');
            errorMessage.textContent = 'Por favor, insira um número de WhatsApp válido';
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
            
            loadingIndicator.style.display = 'block';
            fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        whatsapp: whatsappInput.value,
                        password: passwordValue,
                        remember: document.getElementById('remember-me').checked
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw response;
                    }
                    return response.json();
                })
                .then(data => {
                    loadingIndicator.style.display = 'none';
                    if (data.two_factor_required) {
                        const twoFactorModal = new bootstrap.Modal(document.getElementById('twoFactorModal'));
                        twoFactorModal.show();
                    } else if (data.success) {
                        window.location.href = data.redirect_url;
                    } else {
                        if (data.errors) {
                            for (const [key, value] of Object.entries(data.errors)) {
                                const input = document.getElementById(key);
                                const errorContainer = input.nextElementSibling;
                                const errorMessage = document.createElement('span');
                                errorMessage.classList.add('text-danger');
                                errorMessage.textContent = value;
                                errorContainer.appendChild(errorMessage);
                            }
                        }
                    }
                })
                .catch(error => {
                    loadingIndicator.style.display = 'none';
                    if (error.json) {
                        error.json().then(err => {
                            if (err.errors) {
                                for (const [key, value] of Object.entries(err.errors)) {
                                    const errorContainer = form.querySelector('.error-container');
                                    const errorMessage = document.createElement('span');
                                    errorMessage.classList.add('text-danger');
                                    errorMessage.textContent = value;
                                    errorContainer.appendChild(errorMessage);
                                }
                            }
                        });
                    } else {
                        console.error('Erro ao fazer login:', error);
                    }
                });
        }
    });

    twoFactorForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const errorMessages = twoFactorForm.querySelectorAll('.text-danger');
        errorMessages.forEach(function(message) {
            message.remove();
        });

        const twoFactorCode = Array.from(digitInputs).map(input => input.value).join('');
        fetch(twoFactorForm.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    two_factor_code: twoFactorCode
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw response;
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect_url;
                } else {
                    if (data.errors) {
                        for (const [key, value] of Object.entries(data.errors)) {
                            const errorContainer = twoFactorForm.querySelector('.error-container');
                            const errorMessage = document.createElement('span');
                            errorMessage.classList.add('text-danger');
                            errorMessage.textContent = value;
                            errorContainer.appendChild(errorMessage);
                        }
                    }
                }
            })
            .catch(error => {
                if (error.json) {
                    error.json().then(err => {
                        if (err.errors) {
                            for (const [key, value] of Object.entries(err.errors)) {
                                const errorContainer = twoFactorForm.querySelector('.error-container');
                                const errorMessage = document.createElement('span');
                                errorMessage.classList.add('text-danger');
                                errorMessage.textContent = value;
                                errorContainer.appendChild(errorMessage);
                            }
                        }
                    });
                } else {
                    console.error('Erro ao verificar o código de 2FA:', error);
                }
            });
    });

});
</script>
@endsection
@section('content')
    <div class="authentication-wrapper authentication-cover authentication-bg">
        <div class="authentication-inner row">
            <!-- /Left Text -->
            <div class="d-none d-lg-flex col-lg-7 p-0">
                <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
                    <img src="{{ asset('assets/img/illustrations/auth-login-illustration-' . $configData['style'] . '.png') }}"
                        alt="auth-login-cover" class="img-fluid my-5 auth-illustration"
                        data-app-light-img="illustrations/auth-login-illustration-light.png"
                        data-app-dark-img="illustrations/auth-login-illustration-dark.png">

                    <img src="{{ asset('assets/img/illustrations/bg-shape-image-' . $configData['style'] . '.png') }}"
                        alt="auth-login-cover" class="platform-bg"
                        data-app-light-img="illustrations/bg-shape-image-light.png"
                        data-app-dark-img="illustrations/bg-shape-image-dark.png">
                </div>
            </div>

            <div class="d-flex col-12 col-lg-5 align-items-center p-sm-5 p-4">
                <div class="w-px-400 mx-auto">
                    <!-- Logo -->
                    <div class="app-brand mb-4">
                        <a href="{{ url('/') }}" class="app-brand-link gap-2">
                            <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 20, 'withbg' => 'fill: #fff;'])</span>
                        </a>
                    </div>

                    
                    
                    <!-- /Logo -->
                    <h3 class=" mb-1">Bem-vindo ao {{ config('variables.templateName') }}! 👋</h3>
                    <p class="mb-4">Por favor, faça login na sua conta usando seu número de WhatsApp</p>

                    <form id="formAuthentication" class="mb-3" action="{{ route('auth-login-basic-post') }}"
                        method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="whatsapp" class="form-label">Número de WhatsApp</label>
                            <input type="text" class="form-control" id="whatsapp" name="whatsapp"
                                placeholder="Digite seu número de WhatsApp" autofocus>
                            <div class="error-container"></div>
                            @if ($errors->has('whatsapp'))
                                <span class="text-danger">{{ $errors->first('whatsapp') }}</span>
                            @endif
                            @if (session('status'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                    {{ session('status') }}
                                </div>
                            @endif
                        </div>
                        <div class="mb-3 form-password-toggle">
                            <div class="d-flex justify-content-between">
                                <label class="form-label" for="password">Senha</label>
                                <a href="{{ url('auth/forgot-password-basic') }}">
                                    <small>Esqueceu a senha?</small>
                                </a>
                            </div>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password" class="form-control" name="password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="password" />
                                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                            </div>
                            <div class="error-container"></div>
                            @if ($errors->has('password'))
                                <span class="text-danger">{{ $errors->first('password') }}</span>
                            @endif
                        </div>
                                              <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember-me" name="remember">
                                <label class="form-check-label" for="remember-me">
                                    Lembrar-me
                                </label>
                            </div>
                                 <p class="mb-0">
                                <a href="{{url('auth/register-basic')}}">
                                    <span>Registre-se</span>
                                </a>
                            </p>
                        </div>
                        <div class="mb-3">
                          <button class="btn btn-primary d-grid w-100" type="submit">Entrar</button>
                      </div>
                      <div id="loadingIndicator" class="text-center" style="display: none;">
                          <div class="spinner-border text-primary" role="status">
                              <span class="visually-hidden">Carregando...</span>
                          </div>
                          <p>Por favor, aguarde...</p>
                      </div>
                    </form>
                </div>
            </div>          
             <div class="modal fade" id="twoFactorModal" tabindex="-1" aria-labelledby="twoFactorModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                        <h5 class="modal-title" id="twoFactorModalLabel">Verificação de Dois Fatores</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                         <div class="modal-body">
                            <form id="twoFactorForm" action="{{ route('auth.verify-two-factor') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="two_factor_code" class="form-label">Código de Verificação</label>
                                    <div class="d-flex justify-content-between">
                                        <input type="text" class="form-control text-center mx-1" id="digit1" name="digit1" maxlength="1" required>
                                        <input type="text" class="form-control text-center mx-1" id="digit2" name="digit2" maxlength="1" required>
                                        <input type="text" class="form-control text-center mx-1" id="digit3" name="digit3" maxlength="1" required>
                                        <input type="text" class="form-control text-center mx-1" id="digit4" name="digit4" maxlength="1" required>
                                        <input type="text" class="form-control text-center mx-1" id="digit5" name="digit5" maxlength="1" required>
                                        <input type="text" class="form-control text-center mx-1" id="digit6" name="digit6" maxlength="1" required>
                                    </div>
                                    <div class="error-container mt-2"></div>
                                    @if ($errors->has('two_factor_code'))
                                        <span class="text-danger">{{ $errors->first('two_factor_code') }}</span>
                                    @endif
                                    @if (session('two_factor_code'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                        {{ session('two_factor_code') }}
                                    </div>
                                @endif
                                </div>
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary w-100">Verificar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </div>
@endsection
