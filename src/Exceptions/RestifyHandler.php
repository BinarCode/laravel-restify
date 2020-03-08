<?php

namespace Binaryk\LaravelRestify\Exceptions;

use Binaryk\LaravelRestify\Controllers\RestResponse;
use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException as EntityNotFoundExceptionEloquent;
use Binaryk\LaravelRestify\Exceptions\Guard\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\Guard\GatePolicy;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException as ActionUnauthorizedException;
use Binaryk\LaravelRestify\Restify;
use Closure;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\UnauthorizedException as ValidationUnauthorized;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Throwable;

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
     * @param  Request  $request
     * @param  \Exception|Throwable $exception
     *
     * @return Response|\Symfony\Component\HttpFoundation\Response
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function render($request, $exception)
    {
        with(Restify::$renderCallback, function ($handler) use ($request, $exception) {
            if ($handler instanceof Closure || is_callable($handler)) {
                return call_user_func($handler, $request, $exception);
            }
        });

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
            case $exception instanceof ValidationUnauthorized:
            case $exception instanceof UnauthorizedHttpException:
            case $exception instanceof UnauthenticateException:
            case $exception instanceof GatePolicy:
            case $exception instanceof AuthenticationException:
                $response->addError($exception->getMessage())->auth();
                break;

            case $exception instanceof AuthorizationException:
            case $exception instanceof ActionUnauthorizedException:
            case $exception instanceof AccessDeniedHttpException:
            case $exception instanceof InvalidSignatureException:
                $response->addError($exception->getMessage())->forbidden();
                break;

            default:
                if (App::environment('production') === true) {
                    $response->addError(__('messages.something_went_wrong'));
                } else {
                    $response->dump($exception, true);
                }
                $response->error();
        }

        return $response->toResponse($request);
    }

    /**
     * Report or log an exception.
     *
     * @param  \Exception|Throwable  $e
     * @return mixed
     *
     * @throws \Exception
     */
    public function report($e)
    {
        return with(Restify::$reportCallback, function ($handler) use ($e) {
            if (is_callable($handler) || $handler instanceof Closure) {
                return call_user_func($handler, $e);
            }

            return parent::report($e);
        });
    }
}
