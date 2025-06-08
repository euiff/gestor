<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor Veetv</title>
</head>


@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Landing - Front Pages')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/nouislider/nouislider.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/swiper/swiper.css')}}" />
@endsection

@section('page-style')
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/front-page-landing.css')}}" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/nouislider/nouislider.js')}}"></script>
<script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/front-page-landing.js')}}"></script>
@endsection

@section('content')
<div data-bs-spy="scroll" class="scrollspy-example">
  <!-- Hero: Start -->
   <!-- Animação do Hero: Início -->
  <section id="hero-animation">
      <div id="landingHero" class="section-py landing-hero position-relative">
          <div class="container">
              <div class="hero-text-box text-center">
                  <h1 class="text-primary hero-title display-6 fw-bold">Um painel para gerenciar todos os seus servidores</h1>
                  <h2 class="hero-sub-title h6 mb-4 pb-1">
                      Dashboard intuitivo e fácil de configurar para <br class="d-none d-lg-block" />
                      melhorar sua gestão.
                  </h2>
                  <div class="landing-hero-btn d-inline-block position-relative">
                      <span class="hero-btn-item position-absolute d-none d-md-flex text-heading">Junte-se à comunidade
                          <img src="{{asset('assets/img/front-pages/icons/Join-community-arrow.png')}}" alt="Seta para juntar-se à comunidade" class="scaleX-n1-rtl" /></span>
                      <a href="#landingPricing" class="btn btn-primary btn-lg">Obtenha um teste gratuito</a>
                  </div>
              </div>
  </section>
  <!-- Animação do Hero: Fim -->
  <!-- Hero: End -->

   <!-- Funcionalidades Úteis: Início -->
  <section id="landingFeatures" class="section-py landing-features">
      <div class="container">
          <div class="text-center mb-3 pb-1">
              <span class="badge bg-label-primary">Funcionalidades Úteis</span>
          </div>
          <h3 class="text-center mb-1">
              <span class="section-title">Tudo o que você precisa</span> para começar seu sistema de cobranças
          </h3>
          <p class="text-center mb-3 mb-md-5 pb-3">
              Tenha em mãos um roteiro completo com ferramentas incríveis que você poderá fornecer a seus clientes.
          </p>
          <div class="features-icon-wrapper row gx-0 gy-4 g-sm-5">
              <div class="col-lg-4 col-sm-6 text-center features-icon-box">
                  <div class="text-center mb-3">
                      <img src="{{asset('assets/img/front-pages/icons/laptop.png')}}" alt="laptop carregando" />
                  </div>
                  <h5 class="mb-3">Gerenciamento de Clientes</h5>
                  <p class="features-icon-description">
                      Organize e gerencie facilmente listas de clientes que contrataram seu produto, garantindo um acompanhamento eficiente.
                  </p>
              </div>
              <div class="col-lg-4 col-sm-6 text-center features-icon-box">
                  <div class="text-center mb-3">
                      <img src="{{asset('assets/img/front-pages/icons/rocket.png')}}" alt="foguete" />
                  </div>
                  <h5 class="mb-3">Criação de Cobranças Personalizadas</h5>
                  <p class="features-icon-description">
                      Personalize suas cobranças utilizando variáveis otimizadas, ajustando os detalhes para atender às necessidades de cada cliente. Crie quantos templates desejar para diferentes situações.
                  </p>
              </div>
              <div class="col-lg-4 col-sm-6 text-center features-icon-box">
                  <div class="text-center mb-3">
                      <img src="{{asset('assets/img/front-pages/icons/paper.png')}}" alt="papel" />
                  </div>
                  <h5 class="mb-3">Pagamentos Automatizados</h5>
                  <p class="features-icon-description">
                      Integração com sistemas de pagamento automáticos para facilitar as transações, reduzindo o trabalho manual e evitando erros.
                  </p>
              </div>
              <div class="col-lg-4 col-sm-6 text-center features-icon-box">
                  <div class="text-center mb-3">
                      <img src="{{asset('assets/img/front-pages/icons/check.png')}}" alt="seleção 3d sólida" />
                  </div>
                  <h5 class="mb-3">Testado e Aprovado</h5>
                  <p class="features-icon-description">
                      Validado por profissionais da área que compreendem bem suas necessidades.
                  </p>
              </div>
              <div class="col-lg-4 col-sm-6 text-center features-icon-box">
                  <div class="text-center mb-3">
                      <img src="{{asset('assets/img/front-pages/icons/user.png')}}" alt="suporte" />
                  </div>
                  <h5 class="mb-3">Suporte Excelente</h5>
                  <p class="features-icon-description">
                      Tenha acesso a suporte e atualizações de forma contínua.
                  </p>
              </div>
              <div class="col-lg-4 col-sm-6 text-center features-icon-box">
                  <div class="text-center mb-3">
                      <img src="{{asset('assets/img/front-pages/icons/keyboard.png')}}" alt="documentação" />
                  </div>
                  <h5 class="mb-3">Material de Apoio</h5>
                  <p class="features-icon-description">
                      Um material bem explicativo para ajudá-lo da melhor forma possível.
                  </p>
              </div>
          </div>
      </div>
  </section>
  <!-- Funcionalidades Úteis: Fim -->

  <!-- Avaliações de Clientes Reais: Início -->
  <section id="landingReviews" class="section-py bg-body landing-reviews pb-0">
      <!-- O que as pessoas dizem slider: Início -->
      <div class="container">
          <div class="row align-items-center gx-0 gy-4 g-lg-5">
              <div class="col-md-6 col-lg-5 col-xl-3">
                  <div class="mb-3 pb-1">
                      <span class="badge bg-label-primary">Avaliações de Clientes Reais</span>
                  </div>
                  <h3 class="mb-1"><span class="section-title">O que as pessoas dizem</span></h3>
                  <p class="mb-3 mb-md-5">
                      Veja o que nossos clientes têm a<br class="d-none d-xl-block" />
                      dizer sobre sua experiência.
                  </p>
                  <div class="landing-reviews-btns">
                      <button id="reviews-previous-btn" class="btn btn-label-primary reviews-btn me-3 scaleX-n1-rtl" type="button">
                          <i class="ti ti-chevron-left ti-sm"></i>
                      </button>
                      <button id="reviews-next-btn" class="btn btn-label-primary reviews-btn scaleX-n1-rtl" type="button">
                          <i class="ti ti-chevron-right ti-sm"></i>
                      </button>
                  </div>
              </div>
              <div class="col-md-6 col-lg-7 col-xl-9">
                  <div class="swiper-reviews-carousel overflow-hidden mb-5 pb-md-2 pb-md-3">
                      <div class="swiper" id="swiper-reviews">
                          <div class="swiper-wrapper">
                              <div class="swiper-slide">
                                  <div class="card h-100">
                                      <div class="card-body text-body d-flex flex-column justify-content-between h-100">
                                          <div class="mb-3">
                                              
                                          </div>
                                          <p>
                                              “Com o sistema de cobranças automatizadas, minha vida mudou! Não preciso mais me preocupar com lembretes manuais. O sistema avisa meus clientes automaticamente pelo WhatsApp, garantindo uma gestão impecável. Recomendo!”
                                          </p>
                                          <div class="text-warning mb-3">
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                          </div>
                                          <div class="d-flex align-items-center">
                                              <div class="avatar me-2 avatar-sm">
                                                  <img src="{{asset('assets/img/avatars/8.png')}}" alt="Avatar" class="rounded-circle" />
                                              </div>
                                              <div>
                                                  <h6 class="mb-0">Cecilia Payne</h6>
                                                  <p class="small text-muted mb-0">CEO da Airbnb</p>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                              <div class="swiper-slide">
                                  <div class="card h-100">
                                      <div class="card-body text-body d-flex flex-column justify-content-between h-100">
                                          <div class="mb-3">
                                              
                                          </div>
                                          <p>
                                              “A automação de cobranças via WhatsApp é sensacional! Meus clientes recebem os lembretes de pagamento no prazo certo, e isso reduziu a inadimplência drasticamente. Muito prático e eficiente!”
                                          </p>
                                          <div class="text-warning mb-3">
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                          </div>
                                          <div class="d-flex align-items-center">
                                              <div class="avatar me-2 avatar-sm">
                                                  <img src="{{asset('assets/img/avatars/2.png')}}" alt="Avatar" class="rounded-circle" />
                                              </div>
                                              <div>
                                                  <h6 class="mb-0">Eugenia Moore</h6>
                                                  <p class="small text-muted mb-0">Fundadora da Hubspot</p>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                              <div class="swiper-slide">
                                  <div class="card h-100">
                                      <div class="card-body text-body d-flex flex-column justify-content-between h-100">
                                          <div class="mb-3">
                                              
                                          </div>
                                          <p>
                                              O sistema de gestão de cobranças salvou o meu negócio. Com as notificações automáticas, meus clientes nunca esquecem de pagar e a organização das finanças melhorou muito. Vale cada centavo!
                                          </p>
                                          <div class="text-warning mb-3">
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                          </div>
                                          <div class="d-flex align-items-center">
                                              <div class="avatar me-2 avatar-sm">
                                                  <img src="{{asset('assets/img/avatars/3.png')}}" alt="Avatar" class="rounded-circle" />
                                              </div>
                                              <div>
                                                  <h6 class="mb-0">Curtis Fletcher</h6>
                                                  <p class="small text-muted mb-0">Líder de Design na Dribbble</p>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                              <div class="swiper-slide">
                                  <div class="card h-100">
                                      <div class="card-body text-body d-flex flex-column justify-content-between h-100">
                                          <div class="mb-3">
                                              
                                          </div>
                                          <p>
                                              Antes era um caos gerenciar tantas assinaturas. Agora, com o sistema, as cobranças são feitas automaticamente e tenho total controle sobre os pagamentos dos clientes. É uma ferramenta indispensável!
                                          </p>
                                          <div class="text-warning mb-3">
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star ti-sm"></i>
                                          </div>
                                          <div class="d-flex align-items-center">
                                              <div class="avatar me-2 avatar-sm">
                                                  <img src="{{asset('assets/img/avatars/4.png')}}" alt="Avatar" class="rounded-circle" />
                                              </div>
                                              <div>
                                                  <h6 class="mb-0">Sara Smith</h6>
                                                  <p class="small text-muted mb-0">Fundadora da Continental</p>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                              <div class="swiper-slide">
                                  <div class="card h-100">
                                      <div class="card-body text-body d-flex flex-column justify-content-between h-100">
                                          <div class="mb-3">
                                              
                                          </div>
                                          <p>
                                              “Este sistema de cobranças automatizadas via WhatsApp é a solução que faltava para o meu negócio. Além de agilizar os processos, diminuiu os atrasos nos pagamentos e me deu mais tempo para focar no crescimento da minha empresa!”
                                          </p>
                                          <div class="text-warning mb-3">
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                          </div>
                                          <div class="d-flex align-items-center">
                                              <div class="avatar me-2 avatar-sm">
                                                  <img src="{{asset('assets/img/avatars/5.png')}}" alt="Avatar" class="rounded-circle" />
                                              </div>
                                              <div>
                                                  <h6 class="mb-0">Eugenia Moore</h6>
                                                  <p class="small text-muted mb-0">Fundadora da Hubspot</p>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                              <div class="swiper-slide">
                                  <div class="card h-100">
                                      <div class="card-body text-body d-flex flex-column justify-content-between h-100">
                                          <div class="mb-3">
                                              
                                          </div>
                                          <p>
                                              Agora não preciso ficar correndo atrás dos clientes para receber. O sistema manda as mensagens e lembretes automaticamente, e meus clientes pagam sem atrasos. A economia de tempo é incrível!
                                          </p>
                                          <div class="text-warning mb-3">
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star-filled ti-sm"></i>
                                              <i class="ti ti-star ti-sm"></i>
                                          </div>
                                          <div class="d-flex align-items-center">
                                              <div class="avatar me-2 avatar-sm">
                                                  <img src="{{asset('assets/img/avatars/6.png')}}" alt="Avatar" class="rounded-circle" />
                                              </div>
                                              <div>
                                                  <h6 class="mb-0">Sara Smith</h6>
                                                  <p class="small text-muted mb-0">Fundadora da Continental</p>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                          <div class="swiper-button-next"></div>
                          <div class="swiper-button-prev"></div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      <!-- O que as pessoas dizem slider: Fim -->
      <hr class="m-0" />
  </section>
  
  <!-- Avaliações de Clientes Reais: Fim -->

  

  <!-- Planos de Preços: Início -->
<section id="landingPricing" class="section-py bg-body landing-pricing">
    <div class="container">
        <div class="text-center mb-3 pb-1">
            <span class="badge bg-label-primary">Planos de Preços</span>
        </div>
        <h3 class="text-center mb-1"><span class="section-title">Planos de preços personalizados</span> feitos para você</h3>
        <p class="text-center mb-4 pb-3">
            Crie Planos de Assinaturas Personalizáveis: Cobrança mensal ou anual, com limite no número de clientes finais. O gestor irá ajudá-lo a oferecer o melhor serviço possível.
        </p>
        <div class="text-center mb-5">
            <div class="position-relative d-inline-block pt-3 pt-md-0">
                <label class="switch switch-primary me-0">
                    <span class="switch-label">Pagar Mensalmente</span>
                    <input type="checkbox" class="switch-input price-duration-toggler" checked />
                    <span class="switch-toggle-slider">
                        <span class="switch-on"></span>
                        <span class="switch-off"></span>
                    </span>
                    <span class="switch-label">Pagar Anualmente</span>
                </label>
                <div class="pricing-plans-item position-absolute d-flex">
                    <img src="{{asset('assets/img/front-pages/icons/pricing-plans-arrow.png')}}" alt="seta planos de preços" class="scaleX-n1-rtl" />
                    <span class="fw-semibold mt-2 ms-1"> Economize 25%</span>
                </div>
            </div>
        </div>
        <div class="row gy-4 pt-lg-3">
            <!-- Plano Básico: Início -->
            <div class="col-xl-4 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <div class="text-center">
                            <img src="{{asset('assets/img/front-pages/icons/paper-airplane.png')}}" alt="ícone avião de papel" class="mb-4 pb-2" />
                            <h4 class="mb-1">Essencial</h4>
                            <div class="d-flex align-items-center justify-content-center">
                                <span class="price-monthly h1 text-primary fw-bold mb-0">R$24,90</span>
                                <span class="price-yearly h1 text-primary fw-bold mb-0 d-none">R$22,81</span>
                                <sub class="h6 text-muted mb-0 ms-1">/mês</sub>
                            </div>
                            <div class="position-relative pt-2">
                                <div class="price-yearly text-muted price-yearly-toggle d-none">R$268,92 / ano</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Cadastre até 50 clientes
                                </h5>
                            </li>
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Cobranças ilimitadas
                                </h5>
                            </li>
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Gráficos e Analises
                                </h5>
                            </li>
                            
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Api gratuita
                                </h5>
                            </li>
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Criação de Templates
                                </h5>
                            </li>
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Suporte básico
                                </h5>
                            </li>
                        </ul>
                        <div class="d-grid mt-4 pt-3">
                            <a href="{{url('/auth/register-basic')}}" class="btn btn-label-primary">Começar</a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Plano Básico: Fim -->

           <!-- Plano Favorito: Início -->
           <div class="col-xl-4 col-lg-6">
                <div class="card border border-primary shadow-lg">
                    <div class="card-header">
                        <div class="text-center">
                            <img src="{{asset('assets/img/front-pages/icons/plane.png')}}" alt="ícone avião" class="mb-4 pb-2" />
                            <h4 class="mb-1">Enterprise</h4>
                            <div class="d-flex align-items-center justify-content-center">
                                <span class="price-monthly h1 text-primary fw-bold mb-0">R$49,90</span>
                                <span class="price-yearly h1 text-primary fw-bold mb-0 d-none">R$44,91</span>
                                <sub class="h6 text-muted mb-0 ms-1">/mês</sub>
                            </div>
                            <div class="position-relative pt-2">
                                <div class="price-yearly text-muted price-yearly-toggle d-none">R$538,92 /ano</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                             <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Cadastre até 100 clientes
                                </h5>
                            </li>
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Cobranças ilimitadas
                                </h5>
                            </li>
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Gráficos e Analises
                                </h5>
                            </li>
                            
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Api gratuita
                                </h5>
                            </li>
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Criação de Templates
                                </h5>
                            </li>
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Suporte básico
                                </h5>
                            </li>
                        </ul>
                        <div class="d-grid mt-4 pt-3">
                            <a href="{{url('/auth/register-basic')}}" class="btn btn-primary">Começar</a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Plano Favorito: Fim -->

               <!-- Plano Enterprise: Início -->
        <div class="col-xl-4 col-lg-6">
          <div class="card">
            <div class="card-header">
              <div class="text-center">
                <img src="{{asset('assets/img/front-pages/icons/shuttle-rocket.png')}}" alt="ícone de foguete" class="mb-4 pb-2" />
                <h4 class="mb-1">Franquia</h4>
                <div class="d-flex align-items-center justify-content-center">
                  <span class="price-monthly h1 text-primary fw-bold mb-0">R$99,90</span>
                  <span class="price-yearly h1 text-primary fw-bold mb-0 d-none">R$89,91</span>
                  <sub class="h6 text-muted mb-0 ms-1">/mês</sub>
                </div>
                <div class="position-relative pt-2">
                  <div class="price-yearly text-muted price-yearly-toggle d-none">R$1078,92 /ano</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <ul class="list-unstyled">
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Cadastro de clientes ilimitado
                                </h5>
                            </li>
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Cadastro de clientes ilimitado
                                </h5>
                            </li>
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Cobranças ilimitadas
                                </h5>
                            </li>
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Gráficos e Analises
                                </h5>
                            </li>
                            
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Api gratuita
                                </h5>
                            </li>
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Criação de Templates
                                </h5>
                            </li>
                            <li>
                                <h5>
                                    <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-xs"></i></span>
                                    Suporte básico
                                </h5>
                            </li>
                        </ul>
              <div class="d-grid mt-4 pt-3">
                <a href="{{url('/auth/register-basic')}}" class="btn btn-label-primary">Começar</a>
              </div>
            </div>
          </div>
        </div>
        <!-- Plano Enterprise: Fim -->
      </div>
    </div>
  </section>
  <!-- Pricing plans: End -->

    <!-- Fatos Divertidos: Início -->
  <section id="landingFunFacts" class="section-py landing-fun-facts">
      <div class="container">
          <div class="row gy-3">
              <div class="col-sm-6 col-lg-3">
                  <div class="card border border-label-primary shadow-none">
                      <div class="card-body text-center">
                          <img src="{{asset('assets/img/front-pages/icons/laptop.png')}}" alt="laptop" class="mb-2" />
                          <h5 class="h2 mb-1">7.1k+</h5>
                          <p class="fw-medium mb-0">
                              Clientes<br />
                              Cadastrados
                          </p>
                      </div>
                  </div>
              </div>
              <div class="col-sm-6 col-lg-3">
                  <div class="card border border-label-success shadow-none">
                      <div class="card-body text-center">
                          <img src="{{asset('assets/img/front-pages/icons/user-success.png')}}" alt="usuário" class="mb-2" />
                          <h5 class="h2 mb-1">50k+</h5>
                          <p class="fw-medium mb-0">
                              Faturas<br />
                              Disparadas
                          </p>
                      </div>
                  </div>
              </div>
              <div class="col-sm-6 col-lg-3">
                  <div class="card border border-label-info shadow-none">
                      <div class="card-body text-center">
                          <img src="{{asset('assets/img/front-pages/icons/diamond-info.png')}}" alt="diamante" class="mb-2" />
                          <h5 class="h2 mb-1">4.9/5</h5>
                          <p class="fw-medium mb-0">
                              Produtos Altamente<br />
                              Avaliados
                          </p>
                      </div>
                  </div>
              </div>
              <div class="col-sm-6 col-lg-3">
                  <div class="card border border-label-warning shadow-none">
                      <div class="card-body text-center">
                          <img src="{{asset('assets/img/front-pages/icons/check-warning.png')}}" alt="garantia" class="mb-2" />
                          <h5 class="h2 mb-1">100%</h5>
                          <p class="fw-medium mb-0">
                              Garantia de<br />
                              De qualidade
                          </p>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </section>
  <!-- Fatos Divertidos: Fim -->

 <!-- FAQ: Start -->
<section id="landingFAQ" class="section-py bg-body landing-faq">
    <div class="container">
        <div class="text-center mb-3 pb-1">
            <span class="badge bg-label-primary">FAQ</span>
        </div>
        <h3 class="text-center mb-1">Perguntas <span class="section-title">Frequentes</span></h3>
        <p class="text-center mb-5 pb-3">Encontre respostas para dúvidas comuns sobre a administração financeira do nosso sistema de gestão para IPTV.</p>
        <div class="row gy-5">
            <div class="col-lg-5">
                <div class="text-center">
                    <img src="{{asset('assets/img/front-pages/landing-page/faq-boy-with-logos.png')}}" alt="faq boy with logos" class="faq-image" />
                </div>
            </div>
            <div class="col-lg-7">
                <div class="accordion" id="accordionExample">
                    <div class="card accordion-item active">
                        <h2 class="accordion-header" id="headingOne">
                            <button type="button" class="accordion-button" data-bs-toggle="collapse" data-bs-target="#accordionOne" aria-expanded="true" aria-controls="accordionOne">
                               Para qual público o Gestor Veetv é indicado?
                            </button>
                        </h2>
                        <div id="accordionOne" class="accordion-collapse collapse show" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                Para quem trabalha no ramo de IPTV e deseja fazer controle e cobranças dos seus clientes de forma prática e automatizada.
                            </div>
                        </div>
                    </div>
                    
                    <div class="card accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionFour" aria-expanded="false" aria-controls="accordionFour">
                               Quais as formas de cobrança?
                            </button>
                        </h2>
                        <div id="accordionFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                Mercado Pago e Pix Manual.
                            </div>
                        </div>
                    </div>
                    <div class="card accordion-item">
                        <h2 class="accordion-header" id="headingFive">
                            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionFive" aria-expanded="false" aria-controls="accordionFive">
                                Quais as formas de monetização?
                            </button>
                        </h2>
                        <div id="accordionFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                               Venda de assinaturas/planos e venda de créditos para que assinantes possam cadastrar seus próprios clientes e oferecer a um preço maior. É um modelo sólido, rentável a longo prazo e sustentável.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

</div>
@endsection
