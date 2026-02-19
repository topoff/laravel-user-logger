<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateULPerformanceLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('performance_logs')) {
            Schema::connection($this->connection)->create('performance_logs', function (Blueprint $table): void {
                $table->bigIncrements('id');

                $table->string('path')->nullable()->index();
                $table->string('method', 12)->nullable()->index();
                $table->unsignedSmallInteger('status')->nullable()->index();
                $table->boolean('booted')->default(false)->index();
                $table->string('skip_reason', 50)->nullable()->index();

                $table->decimal('request_duration_ms', 10, 3)->default(0);
                $table->decimal('boot_duration_ms', 10, 3)->nullable();

                $table->unsignedInteger('queries_total')->nullable();
                $table->unsignedInteger('queries_user_logger')->nullable();
                $table->unsignedBigInteger('log_id')->nullable()->index();
                $table->unsignedBigInteger('domain_id')->nullable()->index();

                $table->json('user_logger_segments')->nullable();
                $table->json('user_logger_counters')->nullable();
                $table->json('user_logger_meta')->nullable();

                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('performance_logs');
    }
}
