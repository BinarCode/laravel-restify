<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Exceptions;

use Binaryk\LaravelRestify\Exceptions\RestifyHandler;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Contracts\Debug\ExceptionHandler;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RestifyHandlerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(ExceptionHandler::class, RestifyHandler::class);
    }

    #[Test]
    #[TestWith([['restify.ai_solutions' => false]], 'ai_solutions is disabled')]
    #[TestWith([['app.debug' => false]], 'outside of debug mode')]
    #[TestWith([['openai.api_key' => null]], 'without an openai key')]
    public function it_does_not_append_a_solution_when_a_guard_blocks_it(array $configOverride): void
    {
        config(array_merge([
            'cache.default' => 'array',
            'restify.ai_solutions' => ['model' => 'gpt-4.1-mini', 'max_tokens' => 1000],
            'app.debug' => true,
            'openai.api_key' => 'sk-test',
        ], $configOverride));

        OpenAI::fake();

        $response = $this->getJson(Restify::path('a-repository-that-does-not-exist'));

        $response->assertInternalServerError()
            ->assertJsonMissingPath('restify-solution');

        OpenAI::assertNothingSent();
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
