<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson()) {
                return $this->handleApiException($e, $request);
            }
        });
    }

    /**
     * Handle API exceptions and return friendly JSON responses
     *
     * @param \Throwable $exception
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleApiException(Throwable $exception, $request)
    {
        // Handle model not found exceptions (e.g., Outage::findOrFail(2))
        if ($exception instanceof ModelNotFoundException) {
            $modelName = class_basename($exception->getModel());
            return new JsonResponse([
                'message' => "The requested {$modelName} could not be found or does not exist.",
            ], 404);
        }

        // Handle 404 Not Found errors
        if ($exception instanceof NotFoundHttpException) {
            return new JsonResponse([
                'message' => 'The requested resource could not be found.',
            ], 404);
        }

        // Handle authorization exceptions
        if ($exception instanceof AuthorizationException || $exception instanceof AccessDeniedHttpException) {
            return new JsonResponse([
                'message' => 'You do not have permission to access this resource.',
            ], 403);
        }

        // Handle validation exceptions
        if ($exception instanceof ValidationException) {
            return new JsonResponse([
                'message' => 'The provided data was invalid.',
                'errors' => $exception->errors(),
            ], 422);
        }
        

        // Handle WeatherServiceException
        if ($exception instanceof WeatherServiceException) {
            return new JsonResponse([
                'message' => 'Weather service is currently unavailable. Please try again later.',
            ], 503);
        }

        

        // If we're in debug mode, return detailed error information
        if (config('app.debug')) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->map(function ($trace) {
                    return [
                        'file' => $trace['file'] ?? null,
                        'line' => $trace['line'] ?? null,
                        'function' => $trace['function'] ?? null,
                        'class' => $trace['class'] ?? null,
                    ];
                })->all(),
            ], 500);
        }

        // In production, return a generic error message
        return new JsonResponse([
            'message' => 'An unexpected error occurred. If this problem persists, please contact support.',
        ], 500);
    }
}