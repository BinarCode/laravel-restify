<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\SecondaryConnectionCompany;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\SecondaryConnectionCompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;

class SyncTransactionConnectionTest extends IntegrationTestCase
{
    #[Test]
    public function sync_opens_its_transaction_on_the_relations_connection(): void
    {
        $defaultConnection = DB::getDefaultConnection();
        $secondaryConnection = SecondaryConnectionCompany::CONNECTION;

        config(["database.connections.{$secondaryConnection}" => config("database.connections.{$defaultConnection}")]);
        DB::connection($secondaryConnection)->setPdo(DB::connection($defaultConnection)->getPdo());

        Restify::repositories([SecondaryConnectionCompanyRepository::class]);

        $company = SecondaryConnectionCompany::query()->create(['name' => 'Acme']);
        $user = User::factory()->create();

        /** @var list<string> $transactionConnections */
        $transactionConnections = [];

        Event::listen(TransactionBeginning::class, function (TransactionBeginning $event) use (&$transactionConnections): void {
            $transactionConnections[] = $event->connectionName;
        });

        $this->postJson(SecondaryConnectionCompanyRepository::route("{$company->getKey()}/sync/staff"), [
            'staff' => [$user->getKey()],
        ])->assertOk();

        $this->assertSame([$secondaryConnection], $transactionConnections);

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }
}
