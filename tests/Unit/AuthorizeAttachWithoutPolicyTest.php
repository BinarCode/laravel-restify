<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthorizeAttachWithoutPolicyTest extends IntegrationTestCase
{
    #[Test]
    public function attach_is_forbidden_when_the_parent_has_no_policy(): void
    {
        $repository = $this->repositoryWithoutPolicy();

        try {
            $repository->authorizeToAttach(new RestifyRequest, 'attachUsers', new UnpolicedModel);
            $this->fail('Attaching without a parent policy must be forbidden.');
        } catch (HttpException $exception) {
            $this->assertSame(JsonResponse::HTTP_FORBIDDEN, $exception->getStatusCode());
        }
    }

    #[Test]
    public function sync_is_forbidden_when_the_parent_has_no_policy(): void
    {
        $repository = $this->repositoryWithoutPolicy();

        try {
            $repository->authorizeToSync(new RestifyRequest, 'syncUsers', Collection::make([1, 2]));
            $this->fail('Syncing without a parent policy must be forbidden.');
        } catch (HttpException $exception) {
            $this->assertSame(JsonResponse::HTTP_FORBIDDEN, $exception->getStatusCode());
        }
    }

    #[Test]
    public function detach_is_forbidden_when_the_parent_has_no_policy(): void
    {
        $repository = $this->repositoryWithoutPolicy();

        $this->expectException(AuthorizationException::class);

        $repository->authorizeToDetach(new RestifyRequest, 'detachUsers', new UnpolicedModel);
    }

    private function repositoryWithoutPolicy(): Repository
    {
        return new class extends Repository
        {
            public static function newModel(): Model
            {
                return new UnpolicedModel;
            }
        };
    }
}

class UnpolicedModel extends Model {}
