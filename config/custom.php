<?php

return [
    'custom' => [
        // Layout
        'myLayout' => 'vertical', // Opções: vertical, horizontal, blank, front
        'myTheme' => 'theme-default', // Opções: theme-default, theme-bordered, theme-semi-dark
        'myStyle' => 'dark', // Opções: light, dark, system
        'myRTLSupport' => true, // Suporte para RTL (Right-to-Left)
        'myRTLMode' => false, // Ativar modo RTL
        'hasCustomizer' => true, // Habilitar customizer
        'showDropdownOnHover' => true, // Mostrar dropdown ao passar o mouse
        'displayCustomizer' => false, // Exibir customizer na interface
        'contentLayout' => 'compact', // Layout do conteúdo: compact, wide
        'headerType' => 'fixed', // Tipo de cabeçalho: fixed, static
        'navbarType' => 'fixed', // Tipo de navbar: fixed, static, hidden
        'menuFixed' => true, // Menu fixo
        'menuCollapsed' => false, // Menu colapsado
        'footerFixed' => false, // Rodapé fixo

        // Controles do customizer
        'customizerControls' => [
            'rtl',
            'style',
            'headerType',
            'contentLayout',
            'layoutCollapsed',
            'showDropdownOnHover',
            'layoutNavbarOptions',
            'themes',
        ],
    ],
];