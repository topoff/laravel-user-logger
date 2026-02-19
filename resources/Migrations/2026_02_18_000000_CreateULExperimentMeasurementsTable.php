<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateULExperimentMeasurementsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('experiment_measurements')) {
            Schema::connection($this->connection)->create('experiment_measurements', function (Blueprint $table): void {
                $table->bigIncrements('id');

                $table->uuid('session_id')->index();
                $table->string('feature', 100)->index();
                $table->string('variant', 120)->nullable()->index();

                $table->unsignedBigInteger('first_log_id')->nullable()->index();
                $table->unsignedBigInteger('last_log_id')->nullable()->index();

                $table->unsignedInteger('exposure_count')->default(0);
                $table->unsignedInteger('conversion_count')->default(0);

                $table->timestamp('first_exposed_at')->useCurrent();
                $table->timestamp('last_exposed_at')->useCurrent();
                $table->timestamp('first_converted_at')->nullable();
                $table->timestamp('last_converted_at')->nullable();

                $table->string('last_conversion_event', 100)->nullable();
                $table->string('last_conversion_entity_type', 100)->nullable();
                $table->string('last_conversion_entity_id', 100)->nullable();

                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->unique(['session_id', 'feature']);
            });
        }

        if (Schema::connection($this->connection)->hasTable('experimentlogs')) {
            Schema::connection($this->connection)->drop('experimentlogs');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('experiment_measurements');
    }
}
