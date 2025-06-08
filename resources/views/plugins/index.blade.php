@extends('layouts/layoutMaster')

@section('title', 'Loja de Plugins')

@section('content')
    <div class="container">
        <h4 class="py-3 mb-2">
            <span class="text-muted fw-light">{{ config('variables.templateName', 'TemplateName') }} / </span>Loja de Plugins
        </h4>

        <div class="row">
            @foreach ($plugins as $plugin)
                @php
                    $pluginNameLower = strtolower($plugin['name']);
                    Log::info('Verificando plugin', [
                        'plugin' => $pluginNameLower,
                        'active' => isset($activeModules[$pluginNameLower]),
                    ]);
                @endphp
                <div class="col-md-6 col-lg-6 col-xl-4 col-sm-12 col-12 p-1 mb-3">
                    <div class="row p-2 m-0">
                        <div class="col-md-12 pt-3 card plugin_card popular_card position-relative">
                            @if ($loop->first)
                                <span class="text-body popular-bump position-absolute top-0 end-0 m-2">
                                    <i class="fa-solid fa-fire-flame-curved"></i> Popular
                                </span>
                            @endif
                            <div class="row">
                                <div class="col-md-4 col-4">
                                    <img src="{{ $plugin['image_url'] }}" class="img-fluid rounded-start"
                                        style="border-radius: 10px;" width="95%" alt="{{ $plugin['name'] }}">
                                </div>
                                <div class="col-md-8 col-8">
                                    <h4 class="">{{ $plugin['name'] }}</h4>
                                    <p>
                                        <i class="fa fa-money-bill"></i> R$ {{ $plugin['price'] }}
                                        <br>
                                        <span
                                            style="font-size: 13px;justify-content: normal;line-height: 15px;display: flex;margin-top: 10px;color: #828282!important;">
                                            {{ \Illuminate\Support\Str::limit($plugin['description'], 100) }}
                                        </span>
                                    </p>
                                    @if (!isset($activeModules[$pluginNameLower]))
                                        <button class="d-block mt-2 mb-1 btn w-100 btn-primary"
                                            onclick="initiatePurchase({{ $plugin['id'] }})">Comprar</button>
                                    @else
                                        <span class="badge bg-success mt-2 mb-1">Já adquirido</span>
                                    @endif
                                </div>
                                <div class="col-md-12 pb-4 col-12">
                                    <span>
                                        <i class="fa fa-check-circle" style="color: green; margin-right: 5px;"></i>
                                        <span
                                            class="text-muted fw-light">{{ config('variables.templateName', 'TemplateName') }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Modal de Pagamento -->
    <div class="modal fade" id="purchaseModal" tabindex="-1" aria-labelledby="purchaseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="purchaseModalLabel">Detalhes do Pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="paymentDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Instalação -->
    <div class="modal fade" id="installationModal" tabindex="-1" aria-labelledby="installationModalLabel"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="installationModalLabel">Instalação do Plugin</h5>
                </div>
                <div class="modal-body text-center">
                    <div class="progress">
                        <div id="installationProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                            role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                    <p id="installationStatus" class="mt-3">Iniciando instalação...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="installationCompleteButton" style="display: none;"
                        onclick="closeInstallationModal()">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPaymentId = null; // Variável global para armazenar o payment_id
        let paymentStatusInterval = null; // Variável para armazenar o intervalo de verificação
        let installationProgressInterval = null; // Variável para armazenar o intervalo de progresso da instalação

        function initiatePurchase(pluginId) {
            fetch('{{ route('plugins.initiatePurchase') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        plugin_id: pluginId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                    } else {
                        currentPaymentId = data.payment_id; // Armazenar o payment_id
                        document.getElementById('paymentDetails').innerHTML = `
                                <p>QR Code:</p>
                                <img src="data:image/png;base64,${data.qr_code_base64}" alt="QR Code" class="img-fluid mb-3" style="max-width: 200px; height: auto;">
                                <p>Código de Pagamento:</p>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="paymentCode" value="${data.payload_pix}" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyPaymentCode()">Copiar</button>
                                </div>
                                <p>Status do Pagamento: <span id="paymentStatus">Aguardando pagamento...</span></p>
                            `;
                        var purchaseModal = new bootstrap.Modal(document.getElementById('purchaseModal'));
                        purchaseModal.show();

                        // Iniciar a verificação automática do status do pagamento
                        paymentStatusInterval = setInterval(checkPaymentStatus, 5000); // Verificar a cada 5 segundos
                    }
                })
                .catch(error => {
                    alert('Erro ao iniciar a compra.');
                });
        }

        function checkPaymentStatus() {
            if (!currentPaymentId) {
                alert('Nenhum pagamento em andamento.');
                return;
            }

            fetch(`{{ route('plugins.checkPaymentStatus') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        payment_id: String(currentPaymentId), // Garantir que payment_id é uma string
                        user_id: {{ auth()->user()->id }}
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na resposta da API');
                    }
                    return response.json(); // Obter a resposta como JSON
                })
                .then(data => {
                    if (data.error) {
                        alert('Erro ao verificar o status do pagamento: ' + data.error);
                    } else {
                        document.getElementById('paymentStatus').innerText = data.status;

                        // Se o pagamento for confirmado, parar a verificação automática e iniciar a instalação
                        if (data.status === 'confirmed' || data.status === 'approved') {
                            clearInterval(paymentStatusInterval);
                            initiatePluginInstallation(currentPaymentId, {{ auth()->user()->id }});
                        }
                    }
                })
                .catch(error => {
                    setTimeout(checkPaymentStatus, 5000); // Tentar novamente após 5 segundos
                });
        }

        function initiatePluginInstallation(paymentId, userId) {
            var installationModal = new bootstrap.Modal(document.getElementById('installationModal'));
            installationModal.show();

            const url = '{{ route('plugins.geraNew') }}';


            // Simular progresso de instalação de 0% a 100%
            let progress = 0;
            installationProgressInterval = setInterval(() => {
                if (progress < 100) {
                    progress += 1;
                    updateInstallationProgress(progress, `Instalando... ${progress}%`);
                } else {
                    clearInterval(installationProgressInterval);
                    fetch(url, {
                            method: 'POST', // Alterado para POST
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                payment_id: String(paymentId), // Garantir que payment_id é uma string
                                user_id: {{ auth()->user()->id }}
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Erro na resposta da API');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.error) {
                                alert(data.error);
                            } else {
                                updateInstallationProgress(100, 'Instalação completa!');
                                document.getElementById('installationCompleteButton').style.display = 'block';
                                closeModals(); // Fechar os modais após a instalação
                            }
                        })
                        .catch(error => {
                            setTimeout(() => initiatePluginInstallation(paymentId, userId),
                                5000); // Tentar novamente após 5 segundos
                        });
                }
            }, 100); // Atualizar a cada 100ms para simular progresso gradual
        }

        function updateInstallationProgress(percentage, status) {
            const progressBar = document.getElementById('installationProgressBar');
            const statusText = document.getElementById('installationStatus');
            progressBar.style.width = percentage + '%';
            progressBar.setAttribute('aria-valuenow', percentage);
            statusText.innerText = status;
        }

        function closeModals() {
            var installationModal = bootstrap.Modal.getInstance(document.getElementById('installationModal'));
            var purchaseModal = bootstrap.Modal.getInstance(document.getElementById('purchaseModal'));
            if (installationModal) {
                installationModal.hide();
            }
            if (purchaseModal) {
                purchaseModal.hide();
            }
        }

        function closeInstallationModal() {
            var installationModal = bootstrap.Modal.getInstance(document.getElementById('installationModal'));
            installationModal.hide();
        }
    </script>
@endsection
