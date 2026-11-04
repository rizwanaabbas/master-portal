<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        if (env("APP_ENV") == 'production') {
            $this->reportable(function (Throwable $e) {
                if (app()->bound('sentry')) {
                    app('sentry')->captureException($e);
                }
            });
        }
    }

    public function render($request, Throwable $exception)
    {
        // Check if the exception is an instance of PostTooLargeException
        if ($exception instanceof PostTooLargeException) {
            $message = 'The uploaded file is too large. Please upload a smaller file.';

            // Return a friendly JSON response for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message
                ], 413); // 413 Payload Too Large
            }

            // Return a friendly view for web requests
            return response()->view('errors.file-too-large'); // 413 Payload Too Large

        }

        // Default exception handling
        return parent::render($request, $exception);
    }

    protected function convertExceptionToArray(Throwable $exception)
    {
        $array = parent::convertExceptionToArray($exception);

        if (\App\Modules\Security\Services\SecurityConfigService::hideExceptionDetails()) {
            $array['message'] = \App\Modules\Security\Services\SecurityConfigService::genericClientMessage();
            unset($array['exception'], $array['file'], $array['line'], $array['trace']);
        }

        return $array;
    }
}
