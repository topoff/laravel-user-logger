<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateULPerformanceDailySummariesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('performance_daily_summaries')) {
            Schema::connection($this->connection)->create('performance_daily_summaries', function (Blueprint $table): void {
                $table->bigIncrements('id');

                $table->date('summary_date')->unique();

                // Request volume (number of persisted performance_log rows). When
                // performance.sample_rate < 1.0 this is a sample - sample_rate is
                // stored alongside so the real volume can be extrapolated.
                $table->unsignedInteger('requests')->default(0);
                $table->decimal('sample_rate', 5, 4)->nullable();

                // Latency distribution (ms). Percentiles are nearest-rank.
                $table->decimal('avg_duration_ms', 10, 3)->nullable();
                $table->decimal('p50_duration_ms', 10, 3)->nullable();
                $table->decimal('p95_duration_ms', 10, 3)->nullable();
                $table->decimal('p99_duration_ms', 10, 3)->nullable();
                $table->decimal('max_duration_ms', 10, 3)->nullable();
                $table->unsignedInteger('slow_requests')->default(0);

                // Reliability.
                $table->unsignedInteger('errors_4xx')->default(0);
                $table->unsignedInteger('errors_5xx')->default(0);

                // Cold starts / boot cost.
                $table->unsignedInteger('cold_boots')->default(0);
                $table->decimal('avg_boot_duration_ms', 10, 3)->nullable();

                // Database load per request.
                $table->decimal('avg_queries', 10, 2)->nullable();
                $table->unsignedInteger('max_queries')->nullable();

                // Business correlation: how many sessions were active and how many
                // converted that day, so latency/errors can be related to outcome.
                $table->unsignedInteger('sessions')->default(0);
                $table->unsignedInteger('conversions')->default(0);
                $table->decimal('conversion_rate', 8, 5)->nullable();

                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('performance_daily_summaries');
    }
}
