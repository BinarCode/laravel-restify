<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Exceptions\Solutions;

use Binaryk\LaravelRestify\Exceptions\RepositoryNotFoundException;
use Binaryk\LaravelRestify\Exceptions\Solutions\OpenAiSolution;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Resources\Chat;
use OpenAI\Responses\Chat\CreateResponse;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

class OpenAiSolutionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
    }

    #[Test]
    public function it_describes_itself_and_asks_the_configured_model_for_a_solution(): void
    {
        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => ['content' => 'Register the repository before using it.'],
                    ],
                ],
            ]),
        ]);

        $solution = new OpenAiSolution($this->aRealThrownException());

        $this->assertSame('AI Generated Solution', $solution->getSolutionTitle());
        $this->assertSame([], $solution->getDocumentationLinks());
        $this->assertSame(
            'Register the repository before using it.',
            trim($solution->getSolutionDescription()),
        );

        OpenAI::assertSent(Chat::class);
    }

    private function aRealThrownException(): Throwable
    {
        try {
            Restify::repository('a-repository-that-does-not-exist');
        } catch (Throwable $e) {
            $this->assertInstanceOf(RepositoryNotFoundException::class, $e);

            return $e;
        }

        $this->fail('Restify::repository() was expected to throw.');
    }
}
