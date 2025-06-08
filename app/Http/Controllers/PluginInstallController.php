<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PluginService;

class PluginInstallController extends Controller
{
    protected $pluginService;

    public function __construct(PluginService $pluginService)
    {
        $this->pluginService = $pluginService;
    }

    public function GeraNew(Request $request)
    {
        return $this->pluginService->generateNewPlugin($request);
    }
}