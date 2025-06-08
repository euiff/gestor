<div class="company-logos">
    @php
        // Obtendo o ID do usuário logado
        $userId = Auth::id();

        // Buscando os dados da tabela company_details para o usuário logado
        $companyDetails = DB::table('company_details')
            ->where('user_id', $userId)
            ->first();
    @endphp

    @if ($companyDetails)
        <div class="company-logo">
            @php
                // Caminhos completos dos logotipos
                $logoLightPath = public_path($companyDetails->company_logo_light);
                $logoDarkPath = public_path($companyDetails->company_logo_dark);
            @endphp

            <!-- Logo para o tema claro -->
            @if (!empty($companyDetails->company_logo_light) && file_exists($logoLightPath))
                <img src="https://gestor.veetv.fun/assets/img/logos/logo-light.png" 
                     class="logo logo-light" 
                     width="32" 
                     height="22" 
                     alt="Company Logo Light">
            @endif

            <!-- Logo para o tema escuro -->
            @if (!empty($companyDetails->company_logo_dark) && file_exists($logoDarkPath))
                <img src="https://gestor.veetv.fun/assets/img/logos/logo-dark.png" 
                     class="logo logo-dark" 
                     width="32" 
                     height="22" 
                     alt="Company Logo Dark">
            @endif

            <!-- Fallback SVG caso nenhum logo esteja disponível -->
            @if (empty($companyDetails->company_logo_light) && empty($companyDetails->company_logo_dark))
               <img src="https://gestor.veetv.fun/assets/img/logos/logo-dark.png" 
                     class="logo logo-dark" 
                     width="32" 
                     height="22" 
                     alt="Company Logo Dark">
            @endif
        </div>
    @else
        <!-- Fallback SVG caso não haja dados da empresa -->
        <img src="https://iili.io/3zLjP14.png" 
                     class="logo logo-white" 
                     width="32" 
                     height="22" 
                     alt="Company Logo White">
    @endif
</div>

<!-- CSS para alternar entre as logos -->
<style>
/* Oculta ambas as logos por padrão */
.logo-light,
.logo-dark {
    display: none !important;
}

/* Exibe a logo do tema claro quando o tema claro está ativo */
body:not(.dark-theme) .logo-light {
    display: block !important;
}

/* Exibe a logo do tema escuro quando o tema escuro está ativo */
body.dark-theme .logo-dark {
    display: block !important;
}

</style>