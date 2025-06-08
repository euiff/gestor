
<?php $__env->startPush('pricing-script'); ?>
  <script src="https://sdk.mercadopago.com/js/v2"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      <?php if(Auth::check() && !$isAdmin && !$isClient && !$isRevendedor && $isExpired): ?>
        var pricingModal = new bootstrap.Modal(document.getElementById('pricingModal'));
        pricingModal.show();

        // Impedir que o modal seja fechado
        var modalElement = document.getElementById('pricingModal');
        modalElement.addEventListener('hide.bs.modal', function (event) {
          event.preventDefault();
        });
      <?php endif; ?>

      // Obter o MERCADO_PAGO_PUBLIC_KEY e o MERCADO_PAGO_SITE_ID das configurações
      const MERCADO_PAGO_PUBLIC_KEY = '<?php echo e(config('mercado_pago.public_key')); ?>';
      const MERCADO_PAGO_SITE_ID = '<?php echo e(config('mercado_pago.site_id')); ?>';

      // Verificar se as variáveis estão definidas corretamente
      if (!MERCADO_PAGO_PUBLIC_KEY || !MERCADO_PAGO_SITE_ID) {
        console.error('Public Key ou Site ID não definidos corretamente.');
        return;
      }

      // Inicializar o MercadoPago
      const mp = new MercadoPago(MERCADO_PAGO_PUBLIC_KEY, {
        locale: 'pt-BR',
        site_id: MERCADO_PAGO_SITE_ID
      });
    
    // Alternância entre preços mensais e anuais
    var toggler = document.querySelector('.price-duration-toggler');
    var monthlyPrices = document.querySelectorAll('.price-monthly');
    var yearlyPrices = document.querySelectorAll('.price-yearly');
    var pricingDuration = document.querySelectorAll('.pricing-duration');

    if (toggler) {
        // Função para alternar entre preços mensais e anuais
        function togglePricing() {
            if (toggler.checked) {
                // Mostrar preços anuais
                monthlyPrices.forEach(function (price) {
                    var yearlyPrice = parseFloat(price.dataset.yearlyPrice); // Obter o preço anual
                    var monthlyPrice = (yearlyPrice / 12).toFixed(2); // Calcular o preço mensal com desconto anual
                    price.textContent = monthlyPrice.replace('.', ','); // Atualizar o preço mensal
                });
                yearlyPrices.forEach(function (price) {
                    price.textContent = parseFloat(price.dataset.yearlyPrice).toFixed(2).replace('.', ','); // Atualizar o preço anual
                });
                pricingDuration.forEach(function (duration) {
                    duration.textContent = '/mês'; // Manter "/mês" no preço mensal
                });
            } else {
                // Mostrar preços mensais
                monthlyPrices.forEach(function (price) {
                    price.textContent = parseFloat(price.dataset.monthlyPrice).toFixed(2).replace('.', ','); // Preço mensal padrão
                });
                yearlyPrices.forEach(function (price) {
                    price.textContent = parseFloat(price.dataset.yearlyPrice).toFixed(2).replace('.', ','); // Preço anual
                });
                pricingDuration.forEach(function (duration) {
                    duration.textContent = '/mês'; // Manter "/mês" no preço mensal
                });
            }
        }

        // Adicionar evento de mudança ao toggler
        toggler.addEventListener('change', togglePricing);

        // Disparar o evento 'change' para garantir que o estado inicial esteja correto
        togglePricing();
    }

      // Função para buscar o saldo de ganhos do usuário
      function fetchSaldoGanhos(userId) {
        return fetch(`/api/saldo-ganhos/${userId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              return parseFloat(data.saldo_ganhos);
            } else {
              console.error('Erro ao buscar saldo de ganhos:', data.message);
              return 0;
            }
          })
          .catch(error => {
            console.error('Erro ao buscar saldo de ganhos:', error);
            return 0;
          });
      }

      // Lógica para o clique no botão do plano
      document.querySelectorAll('.btn-plan').forEach(function (button) {
        button.addEventListener('click', async function () {
          var addNewAddressModal = new bootstrap.Modal(document.getElementById('addNewAddress'));
          addNewAddressModal.show();

          // Capturar o ID do plano
          var planoId = button.getAttribute('data-id');
          document.getElementById('planoId').value = planoId;

          // Definir se o plano anual está habilitado
          var isAnual = toggler.checked;
          document.getElementById('isAnual').value = isAnual ? 'true' : 'false';

          // Obter o saldo de ganhos do usuário
          var userId = document.getElementById('userId')?.value;
          if (userId) {
            var saldoGanhos = await fetchSaldoGanhos(userId);
            document.getElementById('saldoGanhos').innerText = saldoGanhos.toFixed(2);
          }
        });
      });

      // Lógica para o método de pagamento
      document.getElementById('addNewAddressForm').addEventListener('change', function (event) {
        var paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value;

        if (paymentMethod === 'pix') {
          // document.getElementById('pix-section').classList.remove('d-none');
          document.getElementById('card-section').classList.add('d-none');
        } else if (paymentMethod === 'credit_card') {
          document.getElementById('pix-section').classList.add('d-none');
          document.getElementById('card-section').classList.remove('d-none');
        }
      });

      function checkPaymentStatus(paymentId) {
        return fetch(`/api/payment-status/${paymentId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              return data.status;
            } else {
              console.error('Erro ao verificar status do pagamento:', data.message);
              return null;
            }
          })
          .catch(error => {
            console.error('Erro ao verificar status do pagamento:', error);
            return null;
          });
      }

      document.getElementById('addNewAddressForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        // Validação de campos
        var userId = document.getElementById('userId')?.value;
        var paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value;
        var planoId = document.getElementById('planoId')?.value;
        var isAnual = document.getElementById('isAnual')?.value === 'true';
        var useSaldoGanhos = document.getElementById('useSaldoGanhos')?.checked;

        if (!userId || !paymentMethod || !planoId) {
          alert('Por favor, preencha todos os campos obrigatórios.');
          return;
        }

        // Obter o saldo de ganhos do usuário
        var saldoGanhos = await fetchSaldoGanhos(userId);

        // Determinar o valor do plano
        var valorPlano = 0;
        if (isAnual) {
          var yearlyPriceElement = document.querySelector('.price-yearly[data-id="' + planoId + '"]');
          if (yearlyPriceElement) {
            valorPlano = parseFloat(yearlyPriceElement.dataset.price);
          }
        } else {
          var monthlyPriceElement = document.querySelector('.price-monthly[data-id="' + planoId + '"]');
          if (monthlyPriceElement) {
            valorPlano = parseFloat(monthlyPriceElement.dataset.price);
          }
        }

        // Aplicar saldo de ganhos ao valor do plano
        var valorFinal = valorPlano;
        if (useSaldoGanhos) {
          valorFinal = Math.max(0, valorPlano - saldoGanhos);
        }

        // Preparar os dados para envio ao backend
        var payload = {
          user_id: userId,
          payment_method: paymentMethod,
          plano_id: planoId,
          isAnual: isAnual,
          use_saldo_ganhos: useSaldoGanhos,
          valor_final: valorFinal
        };

        if (paymentMethod === 'credit_card') {
          var cardNumber = document.getElementById('cardNumber')?.value.replace(/\s+/g, '');
          var cardExpiry = document.getElementById('cardExpiry')?.value.split('/');
          var cardCvc = document.getElementById('cardCvc')?.value;
          var cardHolderName = document.getElementById('cardHolderName')?.value;
          var cardCpf = document.getElementById('cardCpf')?.value;

          if (!cardNumber || !cardExpiry || cardExpiry.length !== 2 || !cardCvc || !cardHolderName || !cardCpf) {
            alert('Preencha todos os campos do cartão de crédito corretamente.');
            return;
          }

          // Validação do formato da data de validade
          if (!/^\d{2}\/\d{2}$/.test(document.getElementById('cardExpiry')?.value)) {
            alert('Formato da data de validade inválido. Use MM/AA.');
            return;
          }

          // Gerar o token do cartão
          mp.createCardToken({
            cardNumber: cardNumber,
            cardExpirationMonth: cardExpiry[0],
            cardExpirationYear: cardExpiry[1],
            securityCode: cardCvc,
            cardholderName: cardHolderName,
            identificationType: 'CPF',
            identificationNumber: cardCpf // Substitua pelo CPF real do usuário
          }).then(function (response) {
            var cardToken = response.id;
            payload.card_token = cardToken;
            payload.card_cpf = cardCpf; // Adicionar cardCpf ao payload
            payload.card_holder_name = cardHolderName; // Adicionar cardHolderName ao payload

            // Enviar a solicitação de pagamento com o token do cartão
            sendPaymentRequest(payload);
          }).catch(function (error) {
            console.error('Erro ao gerar o token do cartão:', error);
            alert('Erro ao gerar o token do cartão. ' + (error.message || 'Verifique os detalhes do cartão e tente novamente.'));
          });
        } else {
          sendPaymentRequest(payload);
        }
      });

      function sendPaymentRequest(payload) {
        fetch('<?php echo e(route('process-payment')); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
          },
          body: JSON.stringify(payload)
        })
        .then(response => {
          if (!response.ok) {
            return response.text().then(text => { throw new Error(text) });
          }
          return response.json();
        })
        .then(data => {
          if (data.success) {
            const saldoCoberto = data.valor_final === 0;

            if (saldoCoberto) {
              document.getElementById('paymentSuccessMessage').classList.remove('d-none');
              document.getElementById('paymentSuccessMessage').innerText = data.message;
            } else if (payload.payment_method === 'pix') {
              // Exibir a seção do PIX
              document.getElementById('pix-section').classList.remove('d-none');
              document.getElementById('card-section').classList.add('d-none');

              // Preencher os dados do PIX
              document.getElementById('pix-qrcode').innerText = data.payload_pix;
              var pixQrcodeBase = document.getElementById('pix-qrcodeBase');
              if (pixQrcodeBase) {
                pixQrcodeBase.src = 'data:image/png;base64,' + data.qr_code_base64;
              }
              document.getElementById('copy-pix-code').classList.remove('d-none');

              // Verificar o status do pagamento periodicamente
              const paymentId = data.payment_id; // Certifique-se de que payment_id está sendo retornado corretamente
              if (!paymentId) {
                console.error('payment_id não definido na resposta do servidor.');
                return;
              }
              const intervalId = setInterval(async () => {
                const status = await checkPaymentStatus(paymentId);
                if (status === 'approved') {
                  clearInterval(intervalId);
                  document.getElementById('paymentSuccessMessage').classList.remove('d-none');
                  document.getElementById('paymentSuccessMessage').innerText = 'Pagamento aprovado com sucesso.';

                  const pricingModal = new bootstrap.Modal(document.getElementById('pricingModal'));
                  const addNewAddressModal = new bootstrap.Modal(document.getElementById('addNewAddress'));
                  pricingModal.hide();
                  addNewAddressModal.hide();
                } else if (status === 'cancelled') {
                  clearInterval(intervalId);
                  document.getElementById('paymentSuccessMessage').classList.remove('d-none');
                  document.getElementById('paymentSuccessMessage').innerText = 'Pagamento cancelado.';
                } else if (status === 'rejected') {
                  clearInterval(intervalId);
                  document.getElementById('paymentSuccessMessage').classList.remove('d-none');
                  document.getElementById('paymentSuccessMessage').innerText = 'Pagamento rejeitado.';
                }
              }, 5000); // Verificar a cada 5 segundos
            } else if (payload.payment_method === 'credit_card') {
              // Exibir a seção do cartão de crédito
              document.getElementById('pix-section').classList.add('d-none');
              document.getElementById('card-section').classList.remove('d-none');

              // Verificar o status do pagamento periodicamente
              const paymentId = data.payment_id; // Certifique-se de que payment_id está sendo retornado corretamente
              if (!paymentId) {
                console.error('payment_id não definido na resposta do servidor.');
                return;
              }
              const intervalId = setInterval(async () => {
                const status = await checkPaymentStatus(paymentId);
                if (status === 'approved') {
                  clearInterval(intervalId);
                  document.getElementById('paymentSuccessMessage').classList.remove('d-none');
                  document.getElementById('paymentSuccessMessage').innerText = 'Pagamento aprovado com sucesso.';

                  const pricingModal = new bootstrap.Modal(document.getElementById('pricingModal'));
                  const addNewAddressModal = new bootstrap.Modal(document.getElementById('addNewAddress'));
                  pricingModal.hide();
                  addNewAddressModal.hide();
                } else if (status === 'cancelled') {
                  clearInterval(intervalId);
                  document.getElementById('paymentSuccessMessage').classList.remove('d-none');
                  document.getElementById('paymentSuccessMessage').innerText = 'Pagamento cancelado.';
                } else if (status === 'rejected') {
                  clearInterval(intervalId);
                  document.getElementById('paymentSuccessMessage').classList.remove('d-none');
                  document.getElementById('paymentSuccessMessage').innerText = 'Pagamento rejeitado.';
                }
              }, 5000); // Verificar a cada 5 segundos
            }
          } else {
            alert('Erro ao processar o pagamento: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Erro:', error);
          alert('Erro ao processar o pagamento: ' + error.message);
        });
      }

      // Lógica para copiar o código PIX
      document.getElementById('copy-pix-code').addEventListener('click', function () {
        var pixCodeElement = document.getElementById('pix-qrcode');
        var range = document.createRange();
        range.selectNode(pixCodeElement);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        try {
          document.execCommand('copy');
          alert('Código PIX copiado para a área de transferência!');
        } catch (err) {
          alert('Erro ao copiar o código PIX.');
        }
        window.getSelection().removeAllRanges();
      });
    });
  </script>
<?php $__env->stopPush(); ?>

<!-- Pricing Modal -->
<div class="modal fade" id="pricingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-simple modal-pricing">
    <div class="modal-content p-2 p-md-5">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <!-- Pricing Plans -->
        <div class="pb-sm-5 pb-2 rounded-top">
          <h2 class="text-center mb-2">Planos de Renovação</h2>
          <p class="text-center">Escolha um plano de assinatura para continuar gerenciando seus clientes IPTV.</p>
          <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 pt-3 mb-4">
            <label class="switch switch-primary ms-3 ms-sm-0 mt-2">
              <span class="switch-label">Mensal</span>
              <input type="checkbox" class="switch-input price-duration-toggler" />
              <span class="switch-toggle-slider">
                <span class="switch-on"></span>
                <span class="switch-off"></span>
              </span>
              <span class="switch-label">Anual</span>
            </label>
            <div class="mt-n5 ms-n5 d-none d-sm-block">
              <i class="ti ti-corner-left-down ti-sm text-muted me-1 scaleX-n1-rtl"></i>
              <span class="badge badge-sm bg-label-primary">Economize até 10%</span>
            </div>
          </div>
          <div class="row mx-0 gy-3">
            <?php $__currentLoopData = $planos_revenda; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plano): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-xl mb-md-0 mb-4">
          <div
          class="card border <?php echo e($plano->id == $current_plan_id ? 'border-primary' : ''); ?> border rounded shadow-none">
          <div class="card-body">
            <div class="my-3 pt-2 text-center">
            <img
              src="<?php echo e(asset('assets/img/illustrations/' . ($loop->first ? 'page-pricing-basic.png' : ($loop->iteration == 2 ? 'page-pricing-standard.png' : 'page-pricing-enterprise.png')))); ?>"
              alt="Image" height="140">
            </div>
            <h3 class="card-title text-center text-capitalize mb-1"><?php echo e($plano->nome); ?></h3>
            <p class="text-center"><?php echo e($plano->descricao); ?></p>
            <div class="text-center h-px-100">
                <div class="d-flex justify-content-center">
                    <!-- Preço Mensal -->
                    <sup class="h6 pricing-currency mt-3 mb-0 me-1 text-primary">R$</sup>
                    <h1 class="display-4 mb-0 text-primary price-monthly" data-monthly-price="<?php echo e($plano->preco); ?>" data-yearly-price="<?php echo e($plano->preco * 12 * 0.9); ?>">
                        <?php echo e(number_format($plano->preco, 2, ',', '.')); ?>

                    </h1>
                    <!-- Duração (Mês/Ano) -->
                    <sub class="h6 pricing-duration mt-auto mb-2 text-muted fw-normal">/mês</sub>
                </div>
                <!-- Preço Anual -->
                <div class="text-center mt-2">
                    <small class="text-muted">
                        R$<span class="price-yearly" data-yearly-price="<?php echo e($plano->preco * 12 * 0.9); ?>">
                            <?php echo e(number_format($plano->preco * 12 * 0.9, 2, ',', '.')); ?>

                        </span> /ano
                    </small>
                </div>
            </div>

            <ul class="list-group ps-3 my-4">
            <li class="mb-2"><?php echo e($plano->detalhes); ?></li>
            </ul>
            
        <?php if($plano->nome === 'Básico'): ?>
        <button type="button"
        class="btn <?php echo e($plano->id == $current_plan_id ? 'btn-primary' : 'btn-label-success'); ?> d-grid w-100 mt-3 btn-plan"
        data-id="<?php echo e($plano->id); ?>" data-bs-dismiss="modal" disabled> Plano Atual</button>
        <?php endif; ?>

            <?php if($plano->nome !== 'Básico'): ?>
        <button type="button"
        class="btn btn-primary d-grid w-100 mt-3 btn-plan"
        data-id="<?php echo e($plano->id); ?>" data-bs-dismiss="modal"><?php echo e($plano->botao); ?></button>
      <?php endif; ?>
          </div>
          </div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </div>
        <!--/ Pricing Plans -->
      </div>
    </div>
  </div>
</div>
<!--/ Pricing Modal -->


<!-- Add New Address Modal -->
<div class="modal fade" id="addNewAddress" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-simple modal-add-new-address">
    <div class="modal-content p-3 p-md-5">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-4">
          <h3 class="address-title mb-2">Adicionar Novo Endereço</h3>
          <p class="text-muted address-subtitle">Escolha o método de pagamento</p>
        </div>
        <form id="addNewAddressForm" class="row g-3" onsubmit="return false">
          <?php if(Auth::check()): ?>
        <input type="hidden" id="userId" value="<?php echo e(Auth::user()->id); ?>">
      <?php endif; ?>
          <input type="hidden" id="planoId" value="">
          <input type="hidden" id="isAnual" value="false">
          <div class="col-12">
            <div class="row">
              <div class="col-md mb-md-0 mb-3">
                <div class="form-check custom-option custom-option-icon">
                  <input class="form-check-input" type="radio" name="paymentMethod" id="pixPayment" value="pix">
                  <label class="form-check-label" for="pixPayment">
                    <span class="option-icon"><i class="bx bxs-credit-card"></i></span>
                    <span class="option-title">PIX</span>
                  </label>
                </div>
              </div>
              <div class="col-md mb-md-0 mb-3">
                <div class="form-check custom-option custom-option-icon">
                  <input class="form-check-input" type="radio" name="paymentMethod" id="creditCardPayment"
                    value="credit_card">
                  <label class="form-check-label" for="creditCardPayment">
                    <span class="option-icon"><i class="bx bx-credit-card"></i></span>
                    <span class="option-title">Cartão de Crédito</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div id="pix-section" class="d-none">
            <div class="alert alert-info" role="alert">
              <p id="pix-code" class="mb-2"></p>
              <img id="pix-qrcodeBase" src="" alt="QR Code PIX" class="img-fluid d-block mx-auto"
                style="max-width: 200px;" />
              <br>
              <pre id="pix-qrcode" class="text-break"
                style="word-wrap: break-word; white-space: pre-wrap; background-color: #f8f9fa; padding: 10px; border-radius: 5px;"></pre>
              <button type="button" id="copy-pix-code" class="btn btn-primary d-none d-block mx-auto">Copiar Código
                PIX</button>
            </div>
          </div>
          <div id="card-section" class="col-12 d-none">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="cardNumber" class="form-label">Número do Cartão</label>
                <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 1234 5678">
              </div>
                            <div class="col-md-3 mb-3">
                  <label for="cardExpiry" class="form-label">Validade</label>
                  <input type="text" class="form-control" id="cardExpiry" placeholder="MM/AA" maxlength="5">
              </div>
              
              <script>
              document.getElementById('cardExpiry').addEventListener('input', function (e) {
                  let input = e.target.value.replace(/\D/g, ''); // Remove todos os caracteres não numéricos
                  if (input.length <= 2) {
                      e.target.value = input;
                  } else if (input.length <= 4) {
                      e.target.value = input.slice(0, 2) + '/' + input.slice(2);
                  } else {
                      e.target.value = input.slice(0, 2) + '/' + input.slice(2, 4);
                  }
              });
              </script>
              <div class="col-md-3 mb-3">
                <label for="cardCvc" class="form-label">CVV</label>
                <input type="text" class="form-control" id="cardCvc" placeholder="123">
              </div>
              <div class="col-12 mb-3">
                <label for="cardHolderName" class="form-label">Titular do Cartão</label>
                <input type="text" class="form-control" id="cardHolderName" placeholder="Nome do Titular">
              </div>
              <div class="form-group">
                <label for="cardCpf">CPF do Titular</label>
                <input type="text" id="cardCpf" class="form-control" placeholder="000.000.000-00">
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="alert alert-warning" role="alert">
              <strong>Seu saldo de Indicações:</strong> R$ <span id="saldoGanhos">0.00</span>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="useSaldoGanhos">
                <label class="form-check-label" for="useSaldoGanhos">
                  Usar saldo de ganhos como desconto
                </label>
              </div>
            </div>
            <!-- verificar o status do pagamento paymentSuccessMessage -->
            <div id="paymentSuccessMessage" class="alert alert-success d-none mt-3" role="alert">
              Pagamento realizado com sucesso usando saldo de ganhos.
            </div>

            <button type="submit" class="btn btn-primary w-100">Pagar</button>
          </div>
        </form>
        <div id="paymentSuccessMessage" class="alert alert-success d-none mt-3" role="alert">
          Pagamento realizado com sucesso usando saldo de ganhos.
        </div>
      </div>
    </div>
  </div>
</div>
<?php /**PATH /www/wwwroot/gestor.veetv.fun/resources/views/_partials/_modals/modal-pricing.blade.php ENDPATH**/ ?>