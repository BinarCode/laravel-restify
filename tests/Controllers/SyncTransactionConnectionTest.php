<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\User\SecondaryConnectionUser;
use Binaryk\LaravelRestify\Tests\Fixtures\User\SecondaryConnectionUserRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;

class SyncTransactionConnectionTest extends IntegrationTestCase
{
    #[Test]
    public function sync_opens_its_transaction_on_the_connection_the_pivot_statements_run_on(): void
    {
        $defaultConnection = DB::getDefaultConnection();
        $secondaryConnection = SecondaryConnectionUser::CONNECTION;

        config(["database.connections.{$secondaryConnection}" => config("database.connections.{$defaultConnection}")]);
        DB::connection($secondaryConnection)->setPdo(DB::connection($defaultConnection)->getPdo());

        Restify::repositories([SecondaryConnectionUserRepository::class]);

        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'secondaryStaff' => BelongsToMany::make('secondaryStaff', SecondaryConnectionUserRepository::class),
            ]);

        $company = Company::factory()->state(['owner_id' => null])->create();
        $user = User::factory()->create();

        /** @var list<string> $transactionConnections */
        $transactionConnections = [];

        Event::listen(TransactionBeginning::class, function (TransactionBeginning $event) use (&$transactionConnections): void {
            $transactionConnections[] = $event->connectionName;
        });

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/secondaryStaff"), [
            'secondaryStaff' => [$user->getKey()],
        ])->assertOk();

        $this->assertSame([$secondaryConnection], $transactionConnections);

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }
}
