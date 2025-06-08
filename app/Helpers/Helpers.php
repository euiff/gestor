<?php

namespace App\Helpers;

use Config;
use Illuminate\Support\Str;

class Helpers
{
    public static function appClasses()
    {
        $data = config('custom.custom');

        $DefaultData = [
            'myLayout' => 'vertical',
            'myTheme' => 'semi-dark',
            'myStyle' => 'dark',
            'myRTLSupport' => true,
            'myRTLMode' => true,
            'hasCustomizer' => true,
            'showDropdownOnHover' => true,
            'displayCustomizer' => false,
            'contentLayout' => 'compact',
            'headerType' => 'fixed',
            'navbarType' => 'fixed',
            'menuFixed' => true,
            'menuCollapsed' => false,
            'footerFixed' => true,
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
        ];

        // Merge default data with custom data
        $data = array_merge($DefaultData, $data);

        // All options available in the template
        $allOptions = [
            'myLayout' => ['vertical', 'horizontal', 'blank', 'front'],
            'menuCollapsed' => [true, false],
            'hasCustomizer' => [true, false],
            'showDropdownOnHover' => [true, false],
            'displayCustomizer' => [true, false],
            'contentLayout' => ['compact', 'wide'],
            'headerType' => ['fixed', 'static'],
            'navbarType' => ['fixed', 'static', 'hidden'],
            'myStyle' => ['light', 'dark', 'system'],
            'myTheme' => ['theme-default', 'theme-bordered', 'theme-semi-dark'],
            'myRTLSupport' => [true, false],
            'myRTLMode' => [true, false],
            'menuFixed' => [true, false],
            'footerFixed' => [true, false],
            'customizerControls' => [],
        ];

        // Validate and set default values
        foreach ($allOptions as $key => $value) {
            if (array_key_exists($key, $DefaultData)) {
                if (gettype($DefaultData[$key]) === gettype($data[$key])) {
                    if (is_string($data[$key])) {
                        if (!isset($data[$key]) || !in_array($data[$key], $value)) {
                            $data[$key] = $DefaultData[$key];
                        }
                    }
                } else {
                    $data[$key] = $DefaultData[$key];
                }
            }
        }

        $styleVal = $data['myStyle'] == "dark" ? "dark" : "light";

        // Layout classes
        $layoutClasses = [
            'layout' => $data['myLayout'],
            'theme' => $data['myTheme'],
            'style' => $styleVal,
            'styleOpt' => $data['myStyle'],
            'rtlSupport' => $data['myRTLSupport'],
            'rtlMode' => $data['myRTLMode'],
            'textDirection' => $data['myRTLMode'],
            'menuCollapsed' => $data['menuCollapsed'],
            'hasCustomizer' => $data['hasCustomizer'],
            'showDropdownOnHover' => $data['showDropdownOnHover'],
            'displayCustomizer' => $data['displayCustomizer'],
            'contentLayout' => $data['contentLayout'],
            'headerType' => $data['headerType'],
            'navbarType' => $data['navbarType'],
            'menuFixed' => $data['menuFixed'],
            'footerFixed' => $data['footerFixed'],
            'customizerControls' => $data['customizerControls'],
        ];

        // Sidebar Collapsed
        $layoutClasses['menuCollapsed'] = $layoutClasses['menuCollapsed'] ? 'layout-menu-collapsed' : '';

        // Header Type
        $layoutClasses['headerType'] = $layoutClasses['headerType'] == 'fixed' ? 'layout-menu-fixed' : '';

        // Navbar Type
        if ($layoutClasses['navbarType'] == 'fixed') {
            $layoutClasses['navbarType'] = 'layout-navbar-fixed';
        } elseif ($layoutClasses['navbarType'] == 'static') {
            $layoutClasses['navbarType'] = '';
        } else {
            $layoutClasses['navbarType'] = 'layout-navbar-hidden';
        }

        // Menu Fixed
        $layoutClasses['menuFixed'] = $layoutClasses['menuFixed'] ? 'layout-menu-fixed' : '';

        // Footer Fixed
        $layoutClasses['footerFixed'] = $layoutClasses['footerFixed'] ? 'layout-footer-fixed' : '';

        // RTL Support
        $layoutClasses['rtlSupport'] = $layoutClasses['rtlSupport'] ? '/rtl' : '';

        // RTL Mode
        $layoutClasses['rtlMode'] = $layoutClasses['rtlMode'] ? 'rtl' : 'ltr';
        $layoutClasses['textDirection'] = $layoutClasses['rtlMode'];

        return $layoutClasses;
    }

    public static function updatePageConfig($pageConfigs)
    {
        $demo = 'custom';
        if (isset($pageConfigs)) {
            foreach ($pageConfigs as $config => $val) {
                Config::set('custom.' . $demo . '.' . $config, $val);
            }
        }
    }
}