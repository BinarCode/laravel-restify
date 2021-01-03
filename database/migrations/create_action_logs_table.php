<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('action_logs', function (Blueprint $table) {
            $table->id();
            $table->char('batch_id', 36);
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->string('name');
            $table->string('actionable_type');
            $table->unsignedBigInteger('actionable_id');
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->text('fields');
            $table->string('status', 25)->default('running');
            $table->text('original')->nullable();
            $table->text('changes')->nullable();
            $table->text('exception');
            $table->timestamps();

            $table->index(['actionable_type', 'actionable_id']);
            $table->index(['batch_id', 'model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('action_logs');
    }
}
