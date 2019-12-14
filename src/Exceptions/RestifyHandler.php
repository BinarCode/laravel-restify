<?php

namespace Binaryk\LaravelRestify\Exceptions;

use Binaryk\LaravelRestify\Controllers\RestResponse;
use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException as EntityNotFoundExceptionEloquent;
use Binaryk\LaravelRestify\Exceptions\Guard\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\Guard\GatePolicy;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyHandler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param \Exception $exception
     *
     * @return Response|\Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $exception)
    {
        $response = new RestResponse();

        switch (true) {
            case $exception instanceof NotFoundHttpException:
            case $exception instanceof ModelNotFoundException:
                $response->addError(__('messages.not_found'))->missing();
                break;
            // These has custom message, that message could be displayed in production.
            case $exception instanceof EntityNotFoundExceptionEloquent:
            case $exception instanceof EntityNotFoundException:
                $response->addError($exception->getMessage())->missing();
                break;

            case $exception instanceof ValidationException:
                $response->errors($exception->errors())->invalid();
                break;

            case $exception instanceof MethodNotAllowedHttpException:
                $response->addError(__('messages.method_not_allowed'))->invalid();
                break;

            case $exception instanceof BadRequestHttpException:
            case $exception instanceof MethodNotAllowedException:
            case $exception instanceof UnauthorizedException:
            case $exception instanceof UnauthorizedHttpException:
            case $exception instanceof UnauthenticateException:
            case $exception instanceof GatePolicy:
            case $exception instanceof AuthenticationException:
                $response->addError($exception->getMessage())->auth();
                break;

            case $exception instanceof AccessDeniedHttpException:
            case $exception instanceof InvalidSignatureException:
                $response->addError($exception->getMessage())->forbidden();
                break;

            default:
                if (App::environment('production')) {
                    $response->addError(__('messages.something_went_wrong'));
                } else {
                    $response->addError($exception->getMessage())->code($exception->getCode())
                        ->line($exception->getLine())
                        ->file($exception->getFile())
                        ->stack($exception->getTraceAsString());
                }
                $response->error();
        }

        return $response->respond();
    }
}
