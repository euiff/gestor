<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    
    }
    public function report(Throwable $exception)
    {
        // Ignora logs para LicenseController não encontrado
        if ($exception instanceof \Illuminate\Contracts\Container\BindingResolutionException) {
            if (str_contains($exception->getMessage(), 'Target class [LicenseController]')) {
                return;
            }
        }
        
        parent::report($exception);
    }
}
