<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLabelsTable extends Migration
{
    public function up(): void
    {
        Schema::create('labels', function (Blueprint $table): void {
            $table->string('code')->collation('NOCASE')->primary();
            $table->timestamps();
        });

        Schema::create('company_label', function (Blueprint $table): void {
            $table->foreignId('company_id')->constrained('companies');
            $table->string('label_code')->collation('NOCASE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_label');
        Schema::dropIfExists('labels');
    }
}
