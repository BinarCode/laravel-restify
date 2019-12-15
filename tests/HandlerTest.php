<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException as EntityNotFoundExceptionEloquent;
use Binaryk\LaravelRestify\Exceptions\Guard\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\Guard\GatePolicy;
use Binaryk\LaravelRestify\Exceptions\RestifyHandler;
use Binaryk\LaravelRestify\Exceptions\UnauthenticateException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\Concerns\InteractsWithContainer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class HandlerTest extends IntegrationTest
{
    use InteractsWithContainer;

    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    private $handler;
    /**
     * @var Request|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = app(RestifyHandler::class);
        $this->request = Mockery::mock(Request::class);
    }

    public function test_404_by_generated_by_framework()
    {
        $response = $this->handler->render($this->request, new NotFoundHttpException('This message is not visible'));
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($response->getData()->errors[0], __('messages.not_found'));
        $this->assertEquals($response->getStatusCode(), 404);

        $response = $this->handler->render($this->request, new ModelNotFoundException('This message is not visible'));
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($response->getData()->errors[0], __('messages.not_found'));
        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function test_404_by_generated_by_developer()
    {
        $response = $this->handler->render($this->request, new EntityNotFoundExceptionEloquent('This message is visible'));
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($response->getData()->errors[0], 'This message is visible');
        $this->assertEquals($response->getStatusCode(), 404);

        $response = $this->handler->render($this->request, new EntityNotFoundException('User'));
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($response->getData()->errors[0], 'Guard entity with policy [User] not found.');
        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function test_400_form_request_validation()
    {
        $validator = Validator::make([], ['email' => 'required'], ['email.required' => 'Email should be fill']);
        $response = $this->handler->render($this->request, new ValidationException($validator));
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(end($response->getData()->errors->email), 'Email should be fill');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function test_400_invalid_verb_method()
    {
        $response = $this->handler->render($this->request, new MethodNotAllowedHttpException(['POST']));
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($response->getData()->errors[0], __('messages.method_not_allowed'));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function test_401_bad_requests_general()
    {
        $response = $this->handler->render($this->request, new BadRequestHttpException('Some message'));
        $this->assertEquals($response->getData()->errors[0], 'Some message');
        $this->assertEquals($response->getStatusCode(), 401);
        $response = $this->handler->render($this->request, new MethodNotAllowedException(['POST'], 'Some message'));
        $this->assertEquals($response->getData()->errors[0], 'Some message');
        $this->assertEquals($response->getStatusCode(), 401);
        $response = $this->handler->render($this->request, new UnauthorizedException('Some message'));
        $this->assertEquals($response->getData()->errors[0], 'Some message');
        $this->assertEquals($response->getStatusCode(), 401);
        $response = $this->handler->render($this->request, new UnauthorizedHttpException('', 'Some message'));
        $this->assertEquals($response->getData()->errors[0], 'Some message');
        $this->assertEquals($response->getStatusCode(), 401);
        $response = $this->handler->render($this->request, new UnauthenticateException('Some message'));
        $this->assertEquals($response->getData()->errors[0], 'Some message');
        $this->assertEquals($response->getStatusCode(), 401);
        $response = $this->handler->render($this->request, new GatePolicy('Some message'));
        $this->assertEquals($response->getData()->errors[0], 'Some message');
        $this->assertEquals($response->getStatusCode(), 401);
        $response = $this->handler->render($this->request, new AuthenticationException('Some message'));
        $this->assertEquals($response->getData()->errors[0], 'Some message');
        $this->assertEquals($response->getStatusCode(), 401);
    }

    public function test_403_forbidden()
    {
        $response = $this->handler->render($this->request, new AccessDeniedHttpException('Some message'));
        $this->assertEquals($response->getData()->errors[0], 'Some message');
        $this->assertEquals($response->getStatusCode(), 403);

        $response = $this->handler->render($this->request, new InvalidSignatureException('Some message'));
        $this->assertEquals($response->getData()->errors[0], 'Invalid signature.');
        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function test_default_unhandled_exception_dev()
    {
        $this->app['config']->set('app.env', 'development');
        $response = $this->handler->render($this->request, new \Exception('Foo'));
        $this->assertObjectHasAttribute('file', $response->getData());
        $this->assertObjectHasAttribute('line', $response->getData());
        $this->assertObjectHasAttribute('errors', $response->getData());
        $this->assertIsArray($response->getData()->errors);
        $this->assertObjectHasAttribute('stack', $response->getData());
    }

    public function test_default_unhandled_exception_production()
    {
        App::shouldReceive('environment')
            ->times(1)
            ->andReturn('production');
        $response = $this->handler->render($this->request, new \Exception('Foo'));
        $this->assertObjectNotHasAttribute('file', $response->getData());
        $this->assertObjectNotHasAttribute('line', $response->getData());
        $this->assertObjectNotHasAttribute('stack', $response->getData());
        $this->assertObjectHasAttribute('errors', $response->getData());
        $this->assertEquals($response->getData()->errors[0], __('messages.something_went_wrong'));
    }
}
