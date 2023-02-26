<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
        // Illegal HTTP methods
        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '405',
                        'source' => ['pointer' => $request->url()],
                        'title' => 'Invalid method',
                        'detail' => 'Targeted resource does not support the requested HTTP method. Please check the documentation.',
                    ],
                ],
            ], 405);
        });

        // When 404 is returned because the targeted resource does not exist
        $this->renderable(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '404',
                        'source' => ['pointer' => $request->url()],
                        'title' => 'Resource not found',
                        'detail' => 'Targeted resource does not exist. Check the URL of the given resource ID.',
                    ],
                ],
            ], 404);
        });

        $this->renderable(function (AuthorizationException $e, $request) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '404',
                        'source' => ['pointer' => $request->url()],
                        'title' => 'Not authorized',
                        'detail' => 'Targeted resource does not exist. Check the URL of the given resource ID.',
                    ],
                ],
            ], 404);
        });

        $this->reportable(function (Throwable $e) {
        });
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'errors' => [
                [
                    'status' => '403',
                    'source' => ['pointer' => $request->url()],
                    'title' => 'Forbidden',
                    'detail' => 'The given resource requires authentication.',
                ],
            ],
        ], 403);
    }
}
