<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Exceptions;

use Binaryk\LaravelRestify\Exceptions\RepositoryNotFoundException;
use Binaryk\LaravelRestify\Exceptions\RestifyHandler;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Contracts\Debug\ExceptionHandler;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use PHPUnit\Framework\Attributes\Test;

class RestifyHandlerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(ExceptionHandler::class, RestifyHandler::class);
    }

    protected function tearDown(): void
    {
        config([
            'app.debug' => false,
            'restify.ai_solutions' => ['model' => 'gpt-4.1-mini', 'max_tokens' => 1000],
            'openai.api_key' => null,
        ]);

        parent::tearDown();
    }

    #[Test]
    public function it_does_not_append_a_solution_when_ai_solutions_is_disabled(): void
    {
        config(['restify.ai_solutions' => false, 'app.debug' => true]);

        $response = $this->getJson(Restify::path('a-repository-that-does-not-exist'));

        $response->assertJsonMissing(['restify-solution'])
            ->assertJson([
                'exception' => RepositoryNotFoundException::class,
            ]);
    }

    #[Test]
    public function it_does_not_append_a_solution_outside_of_debug_mode(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson(Restify::path('a-repository-that-does-not-exist'));

        $response->assertStatus(500)
            ->assertJsonMissing(['restify-solution'])
            ->assertExactJson(['message' => 'Server Error']);
    }

    #[Test]
    public function it_does_not_append_a_solution_without_an_openai_key(): void
    {
        config(['app.debug' => true, 'openai.api_key' => null]);

        $response = $this->getJson(Restify::path('a-repository-that-does-not-exist'));

        $response->assertJsonMissing(['restify-solution'])
            ->assertJson([
                'exception' => RepositoryNotFoundException::class,
            ]);
    }

    #[Test]
    public function it_appends_an_ai_solution_when_fully_configured(): void
    {
        config([
            'cache.default' => 'array',
            'app.debug' => true,
            'openai.api_key' => 'sk-test',
        ]);

        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => ['content' => 'Register the repository before using it.'],
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson(Restify::path('a-repository-that-does-not-exist'));

        $this->assertSame(
            'Register the repository before using it.',
            trim($response->json('restify-solution')),
        );
    }
}
