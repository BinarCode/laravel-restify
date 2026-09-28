<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Repositories\HiddenModelAttributes;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\User\HiddenAttributesUserRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\MergeableHiddenAttributesUserRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\VisibleAttributesUser;
use Binaryk\LaravelRestify\Tests\Fixtures\User\VisibleAttributesUserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class HiddenFieldWarningTest extends IntegrationTestCase
{
    /** @var list<class-string<Repository>> */
    private array $registeredRepositories = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->registeredRepositories = Restify::$repositories;
        Restify::$repositories = [HiddenAttributesUserRepository::class, VisibleAttributesUserRepository::class, ...Restify::$repositories];

        HiddenModelAttributes::flushWarnings();

        config(['app.debug' => true]);

        Log::spy();
    }

    protected function tearDown(): void
    {
        Restify::$repositories = $this->registeredRepositories;

        HiddenModelAttributes::flushWarnings();

        parent::tearDown();
    }

    #[Test]
    #[TestWith([1], 'a show serialisation')]
    #[TestWith([2], 'an index serialisation')]
    public function serialising_a_field_the_model_hides_logs_one_warning_per_repository_and_attribute(int $userCount): void
    {
        $users = UserFactory::new()->count($userCount)->create()->all();

        rest(...$users)->jsonSerialize();
        rest(...$users)->jsonSerialize();

        Log::shouldHaveReceived('warning')
            ->with($this->warning(HiddenAttributesUserRepository::class, 'password', User::class))
            ->once();
        Log::shouldHaveReceived('warning')
            ->with($this->warning(HiddenAttributesUserRepository::class, 'remember_token', User::class))
            ->once();
        Log::shouldHaveReceived('warning')->twice();
    }

    #[Test]
    public function a_field_missing_from_the_visible_list_is_warned_about_unless_it_is_computed_or_never_serialised(): void
    {
        $user = VisibleAttributesUser::query()->findOrFail(UserFactory::one()->getKey());

        rest($user)->jsonSerialize();

        Log::shouldHaveReceived('warning')
            ->with($this->warning(VisibleAttributesUserRepository::class, 'password', VisibleAttributesUser::class))
            ->once();
        Log::shouldHaveReceived('warning')
            ->with($this->warning(VisibleAttributesUserRepository::class, 'remember_token', VisibleAttributesUser::class))
            ->once();
        Log::shouldHaveReceived('warning')->twice();
    }

    #[Test]
    #[TestWith([1], 'a show serialisation')]
    #[TestWith([2], 'an index serialisation')]
    public function a_mergeable_repository_is_not_warned_about_because_it_serialises_the_models_array_form(int $userCount): void
    {
        Restify::$repositories = [MergeableHiddenAttributesUserRepository::class, ...Restify::$repositories];

        $users = UserFactory::new()->count($userCount)->create()->all();

        $serialized = rest(...$users)->jsonSerialize();

        $this->assertNotContains('password', array_keys($userCount === 1 ? $serialized['attributes'] : $serialized['data']->first()['attributes']));
        Log::shouldNotHaveReceived('warning');
    }

    #[Test]
    public function nothing_is_logged_outside_debug_mode(): void
    {
        config(['app.debug' => false]);

        rest(UserFactory::one())->jsonSerialize();

        Log::shouldNotHaveReceived('warning');
    }

    private function warning(string $repository, string $attribute, string $model): string
    {
        return "Restify repository [{$repository}] serializes the [{$attribute}] attribute, which [{$model}] hides. Hide the field from show and index, or remove it.";
    }
}
