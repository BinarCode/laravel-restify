<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Illuminate\Support\Collection;

class CompanyRepository extends Repository
{
    public static $model = Company::class;

    public static function include(): array
    {
        return [
            'users' => BelongsToMany::make('users', UserRepository::class)->withPivot(
                Field::make('is_admin')->rules('required')
            )->canDetach(fn ($request, $pivot) => isset($_SERVER['roles.canDetach.users']) && $_SERVER['roles.canDetach.users']),

            // Deliberately never attachable, so tests can prove a request routed to
            // this relation (instead of the one named by the URL) never writes to it.
            'deniedRoles' => BelongsToMany::make('deniedRoles', RoleRepository::class)
                ->canAttach(fn (): bool => false),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
        ];
    }

    /**
     * Records the `$repositoryId` it was handed, so tests can prove a body
     * `repositoryId` never reaches the hook in place of the route segment.
     */
    public function attach(RestifyRequest $request, $repositoryId, Collection $pivots)
    {
        $_SERVER['CompanyRepository.attach.repositoryId'] = $repositoryId;

        return parent::attach($request, $repositoryId, $pivots);
    }

    public function detach(RestifyRequest $request, $repositoryId, Collection $pivots)
    {
        $_SERVER['CompanyRepository.detach.repositoryId'] = $repositoryId;

        return parent::detach($request, $repositoryId, $pivots);
    }

    public function sync(RestifyRequest $request, $repositoryId, Collection $pivots)
    {
        $_SERVER['CompanyRepository.sync.repositoryId'] = $repositoryId;

        return parent::sync($request, $repositoryId, $pivots);
    }

    public function show(RestifyRequest $request, $repositoryId)
    {
        $_SERVER['CompanyRepository.show.repositoryId'] = $repositoryId;

        return parent::show($request, $repositoryId);
    }

    public function update(RestifyRequest $request, $repositoryId)
    {
        $_SERVER['CompanyRepository.update.repositoryId'] = $repositoryId;

        return parent::update($request, $repositoryId);
    }

    public function patch(RestifyRequest $request, $repositoryId)
    {
        $_SERVER['CompanyRepository.patch.repositoryId'] = $repositoryId;

        return parent::patch($request, $repositoryId);
    }

    public function destroy(RestifyRequest $request, $repositoryId)
    {
        $_SERVER['CompanyRepository.destroy.repositoryId'] = $repositoryId;

        return parent::destroy($request, $repositoryId);
    }
}
