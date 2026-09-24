<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Relations\Pivot;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class BelongsToManySyncAuthorizationTest extends IntegrationTestCase
{
    #[Test]
    #[TestWith([false, true, false], 'canSync denies over an allowing canAttach')]
    #[TestWith([true, false, true], 'canSync allows over a denying canAttach')]
    #[TestWith([null, false, false], 'canAttach still gates sync when canSync is not set')]
    #[TestWith([null, true, true], 'canAttach still allows sync when canSync is not set')]
    #[TestWith([null, null, true], 'sync is allowed when neither is set')]
    public function authorized_to_sync_prefers_can_sync_over_can_attach(?bool $canSync, ?bool $canAttach, bool $expected): void
    {
        $field = BelongsToMany::make('users', UserRepository::class);

        if ($canSync !== null) {
            $field->canSync(fn (RestifyRequest $request, Pivot $pivot): bool => $canSync);
        }

        if ($canAttach !== null) {
            $field->canAttach(fn (RestifyRequest $request, Pivot $pivot): bool => $canAttach);
        }

        $this->assertSame($expected, $field->authorizedToSync(new RestifyRequest, new Pivot));
    }
}
