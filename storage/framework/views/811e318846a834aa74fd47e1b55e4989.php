<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('vendor-style'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/apex-charts/apex-charts.css')); ?>" />
    <link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')); ?>" />
    <link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')); ?>" />
    <link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')); ?>" />
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <script src="<?php echo e(asset('assets/vendor/libs/apex-charts/apexcharts.js')); ?>"></script>
    <!-- <script src="<?php echo e(asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')); ?>"></script> -->
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <script src="<?php echo e(asset('assets/js/app-ecommerce-dashboard.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/js/dashboards-crm.js')); ?>"></script>
    <script>
        function compareVersions(v1, v2) {
            if (!v1 || !v2) return 0;
    
            console.log(`Comparando versões: v1 = ${v1}, v2 = ${v2}`);
    
            const v1Parts = v1.split('.').map(Number);
            const v2Parts = v2.split('.').map(Number);
    
            console.log(`Partes de v1: ${v1Parts}`);
            console.log(`Partes de v2: ${v2Parts}`);
    
            for (let i = 0; i < Math.max(v1Parts.length, v2Parts.length); i++) {
                const v1Part = v1Parts[i] || 0;
                const v2Part = v2Parts[i] || 0;
    
                if (v1Part > v2Part) {
                    console.log(`v1Parts[${i}] (${v1Part}) é maior que v2Parts[${i}] (${v2Part})`);
                    return 1;
                }
                if (v1Part < v2Part) {
                    console.log(`v1Parts[${i}] (${v1Part}) é menor que v2Parts[${i}] (${v2Part})`);
                    return -1;
                }
            }
    
            console.log('As versões são iguais');
            return 0;
        }
    
        function startUpdate(version) {
            const progressContainer = document.getElementById('progress-container');
            const progressBar = document.getElementById('progress-bar');
            const progressPercent = document.getElementById('progress-percent');
            const progressStatus = document.getElementById('progress-status');
    
            progressContainer.style.display = 'block';
    
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo e(route('api.startUpdate')); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', '<?php echo e(csrf_token()); ?>');
    
            xhr.upload.onprogress = function(event) {
                if (event.lengthComputable) {
                    const percentComplete = (event.loaded / event.total) * 100;
                    progressBar.value = percentComplete;
                    progressPercent.textContent = `${Math.round(percentComplete)}%`;
                }
            };
    
            xhr.onprogress = function(event) {
                if (event.lengthComputable) {
                    const percentComplete = (event.loaded / event.total) * 100;
                    progressBar.value = percentComplete;
                    progressPercent.textContent = `${Math.round(percentComplete)}%`;
                }
            };
    
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const data = JSON.parse(xhr.responseText);
                    console.log('Resposta da API de atualização:', data); // Adicionando console.log para ver a resposta da API
                    if (data.success) {
                        progressStatus.textContent = 'Atualização concluída com sucesso!';
                        progressBar.value = 100;
                        progressPercent.textContent = '100%';
                        toastr.success('Atualização concluída com sucesso!');
                        window.location.reload();
                    } else {
                        toastr.error(`Erro ao iniciar atualização: ${data.message}`);
                        progressContainer.style.display = 'none';
                    }
                } else {
                    toastr.error('Erro ao iniciar atualização.');
                    progressContainer.style.display = 'none';
                }
            };
    
            xhr.onerror = function() {
                toastr.error('Erro ao iniciar atualização.');
                progressContainer.style.display = 'none';
            };
    
            xhr.send(JSON.stringify({ version: version }));
        }
    </script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <!-- Verificação de Mensagens de Sessão -->
        <?php if(session('warning')): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <?php echo e(session('warning')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- mensagens para erros -->
        <?php if(session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo e(session('error')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- mensagens para sucesso -->
        <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo e(session('success')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Exibição de Erros de Validação -->
        <?php if($errors->any()): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul>
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="col-xl-8 mb-4 col-lg-7 col-12" style="width:100%">
            <div class="card h-100">
                <div class="card-header">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="card-title mb-0">Estatísticas</h5>
                        <!-- <small class="text-muted">Atualizado há 1 mês</small> -->
                    </div>
                </div>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-3 col-6">
                            <div class="d-flex align-items-center">
                                <div class="badge rounded-pill bg-label-primary me-3 p-2"><i class="ti ti-users ti-sm"></i>
                                </div>
                                <div class="card-info">
                                    <h5 class="mb-0"><?php echo e(number_format($totalClientes, 0, ',', '.')); ?></h5>
                                    <small>Total de clientes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="d-flex align-items-center">
                                <div class="badge rounded-pill bg-label-info me-3 p-2"><i
                                        class="ti ti-chart-pie-2 ti-sm"></i></div>
                                <div class="card-info">
                                    <h5 class="mb-0"><?php echo e(number_format($inadimplentes, 0, ',', '.')); ?></h5>
                                    <small>Inadimplentes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="d-flex align-items-center">
                                <div class="badge rounded-pill bg-label-danger me-3 p-2"><i
                                        class="ti ti-shopping-cart ti-sm"></i></div>
                                <div class="card-info">
                                    <h5 class="mb-0"><?php echo e(number_format($ativos, 0, ',', '.')); ?></h5>
                                    <small>Ativos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="d-flex align-items-center">
                                <div class="badge rounded-pill bg-label-success me-3 p-2"><i
                                        class="ti ti-currency-dollar ti-sm"></i></div>
                                <div class="card-info">
                                    <h5 class="mb-0"><?php echo e(number_format($expiramHoje, 0, ',', '.')); ?></h5>
                                    <small>Expiram hoje</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Estatísticas -->

        <!-- Estatísticas Detalhadas -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-12 position-relative p-4">
                            <div class="card-header d-inline-block p-0 text-wrap position-absolute">
                                <h5 class="m-0 card-title">Estatísticas Detalhadas</h5>
                            </div>
                            <br>
                            <div id="detailedStatisticsChart" class="mt-n1"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Estatísticas Detalhadas -->

        <!-- Transações -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <div class="card-title m-0 me-2">
                        <h5 class="m-0 me-2">Transações</h5>
                        <small class="text-muted" id="transactionCount">Ultimas <?php echo e($pagamentos->count()); ?> transações
                            realizadas</small>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="p-0 m-0" id="transactionList">
                        <?php $__currentLoopData = $pagamentos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pagamento): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="d-flex mb-3 pb-1 align-items-center">
                                <div class="badge bg-label-primary me-3 rounded p-2">
                                    <i class="ti ti-wallet ti-sm"></i>
                                </div>
                                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="me-2">
                                        <h6 class="mb-0">Pagamento</h6>
                                        <small class="text-muted d-block">ID da Transação:
                                            <?php echo e($pagamento->mercado_pago_id); ?></small>
                                    </div>
                                    <div class="user-progress d-flex align-items-center gap-1">
                                        <h6 class="mb-0 text-success">
                                            +R$<?php echo e(number_format($pagamento->valor, 2, ',', '.')); ?></h6>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            </div>
        </div>
        <!--/ Transações -->

        <!-- Relatórios de Ganhos -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <div class="card-title mb-0">
                        <h5 class="mb-0">Relatórios de Ganhos</h5>
                        <small class="text-muted">Visão geral dos ganhos anuais</small>
                    </div>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" id="earningReportsTabsId" data-bs-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                            <i class="ti ti-dots-vertical ti-sm text-muted"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs widget-nav-tabs pb-3 gap-4 mx-1 d-flex flex-nowrap" role="tablist">
                        <li class="nav-item">
                            <a href="javascript:void(0);"
                                class="nav-link btn active d-flex flex-column align-items-center justify-content-center"
                                role="tab" data-bs-toggle="tab" data-bs-target="#navs-orders-id"
                                aria-controls="navs-orders-id" aria-selected="true">
                                <div class="badge bg-label-secondary rounded p-2"><i
                                        class="ti ti-shopping-cart ti-sm"></i></div>
                                <h6 class="tab-widget-title mb-0 mt-2">Ordens</h6>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="javascript:void(0);"
                                class="nav-link btn d-flex flex-column align-items-center justify-content-center"
                                role="tab" data-bs-toggle="tab" data-bs-target="#navs-sales-id"
                                aria-controls="navs-sales-id" aria-selected="false">
                                <div class="badge bg-label-secondary rounded p-2"><i class="ti ti-chart-bar ti-sm"></i>
                                </div>
                                <h6 class="tab-widget-title mb-0 mt-2">Receita</h6>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="javascript:void(0);"
                                class="nav-link btn d-flex flex-column align-items-center justify-content-center"
                                role="tab" data-bs-toggle="tab" data-bs-target="#navs-earnings-id"
                                aria-controls="navs-earnings-id" aria-selected="false">
                                <div class="badge bg-label-secondary rounded p-2"><i
                                        class="ti ti-currency-dollar ti-sm"></i></div>
                                <h6 class="tab-widget-title mb-0 mt-2">Ganhos</h6>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content p-0 ms-0 ms-sm-2">
                        <div class="tab-pane fade show active" id="navs-orders-id" role="tabpanel">
                            <div id="earningReportsTabsOrders"></div>
                        </div>
                        <div class="tab-pane fade" id="navs-sales-id" role="tabpanel">
                            <div id="earningReportsTabsSales"></div>
                        </div>
                        <div class="tab-pane fade" id="navs-profit-id" role="tabpanel">
                            <div id="earningReportsTabsProfit"></div>
                        </div>
                        <div class="tab-pane fade" id="navs-income-id" role="tabpanel">
                            <div id="earningReportsTabsIncome"></div>
                        </div>
                        <div class="tab-pane fade" id="navs-earnings-id" role="tabpanel">
                            <div id="earningsLast7Days"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Relatórios de Ganhos -->

        <script>
            var estatisticas = {
                totalClientes: <?php echo json_encode($totalClientes, 15, 512) ?>,
                inadimplentes: <?php echo json_encode($inadimplentes, 15, 512) ?>,
                ativos: <?php echo json_encode($ativos, 15, 512) ?>,
                expiramHoje: <?php echo json_encode($expiramHoje, 15, 512) ?>
            };

            document.addEventListener('DOMContentLoaded', function() {
                const detailedStatisticsChartEl = document.querySelector('#detailedStatisticsChart');

                // console.log("estatisticas", estatisticas);

                // Defina cores manualmente para os modos
                const chartTextColor = '#333'; // Cor para o modo claro
                const chartFillColors = ['#008ffb', '#33FF57', '#feb019', '#ff4560']; // Cores dos dados
                const legendTextColor = '#a2a2a2'; // Cor para o texto da legenda
                const axisTextColor = '#a2a2a2'; // Cor para o texto dos eixos e títulos

                const detailedStatisticsChartOptions = {
                    series: [{
                            name: 'Total de Clientes',
                            data: [estatisticas.totalClientes]
                        },
                        {
                            name: 'Inadimplentes',
                            data: [estatisticas.inadimplentes]
                        },
                        {
                            name: 'Ativos',
                            data: [estatisticas.ativos]
                        },
                        {
                            name: 'Expiram Hoje',
                            data: [estatisticas.expiramHoje]
                        }
                    ],
                    chart: {
                        height: 350,
                        type: 'bar',
                        toolbar: {
                            show: false
                        },
                        foreColor: chartTextColor
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '55%',
                            endingShape: 'rounded'
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        show: true,
                        width: 2,
                        colors: ['transparent']
                    },
                    xaxis: {
                        categories: ['Estatísticas'],
                        labels: {
                            style: {
                                colors: axisTextColor
                            }
                        }
                    },
                    yaxis: {
                        title: {
                            text: 'Quantidade',
                            style: {
                                color: axisTextColor
                            }
                        },
                        labels: {
                            style: {
                                colors: axisTextColor
                            }
                        }
                    },
                    fill: {
                        opacity: 1,
                        colors: chartFillColors // Define cores de preenchimento dos gráficos
                    },
                    tooltip: {
                        theme: 'light', // Ou 'dark' se preferir
                        y: {
                            formatter: function(val) {
                                return val;
                            }
                        }
                    },
                    legend: {
                        labels: {
                            colors: legendTextColor // Define a cor do texto da legenda
                        }
                    }
                };

                if (detailedStatisticsChartEl !== undefined && detailedStatisticsChartEl !== null) {
                    const detailedStatisticsChart = new ApexCharts(detailedStatisticsChartEl,
                        detailedStatisticsChartOptions);
                    detailedStatisticsChart.render();

                    // console.log("detailedStatisticsChart", detailedStatisticsChart);
                }
            });
        </script>

        <!-- Script para Filtrar Transações -->
        <script>
            function filterTransactions(period) {
                const userId = <?php echo e(auth()->user()->id); ?>; // Obtém o ID do usuário autenticado

                fetch(`/api/transactions?period=${period}&user_id=${userId}`)
                    .then(response => response.json())
                    .then(data => {
                        const transactionList = document.getElementById('transactionList');
                        const transactionCount = document.getElementById('transactionCount');

                        // Atualizar contagem de transações
                        transactionCount.textContent = `Total de ${data.payments.length} transações realizadas`;

                        // Limpar lista de transações
                        transactionList.innerHTML = '';

                        // Adicionar transações filtradas
                        data.payments.forEach(pagamento => {
                            const li = document.createElement('li');
                            li.classList.add('d-flex', 'mb-3', 'pb-1', 'align-items-center');

                            li.innerHTML = `
            <div class="badge bg-label-primary me-3 rounded p-2">
              <i class="ti ti-wallet ti-sm"></i>
            </div>
            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
              <div class="me-2">
                <h6 class="mb-0">Mercado Pago</h6>
                <small class="text-muted d-block">ID da Transação: ${pagamento.mercado_pago_id}</small>
              </div>
              <div class="user-progress d-flex align-items-center gap-1">
                <h6 class="mb-0 text-success">+R$${parseFloat(pagamento.valor).toFixed(2).replace('.', ',')}</h6>
              </div>
            </div>
          `;

                            transactionList.appendChild(li);
                        });
                    })
                    .catch(error => console.error('Erro ao buscar transações:', error));
            }
        </script>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/gestor.veetv.fun/resources/views/content/apps/app-ecommerce-dashboard.blade.php ENDPATH**/ ?>