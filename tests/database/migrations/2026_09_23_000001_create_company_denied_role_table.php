<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyDeniedRoleTable extends Migration
{
    public function up(): void
    {
        Schema::create('company_denied_role', function (Blueprint $table): void {
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('role_id')->constrained('roles');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_denied_role');
    }
}
