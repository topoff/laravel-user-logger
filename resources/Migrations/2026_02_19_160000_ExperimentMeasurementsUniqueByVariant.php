<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class ExperimentMeasurementsUniqueByVariant extends Migration
{
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('experiment_measurements')) {
            return;
        }

        Schema::connection($this->connection)->table('experiment_measurements', function (Blueprint $table): void {
            try {
                $table->dropUnique('experiment_measurements_session_id_feature_unique');
            } catch (\Throwable) {
                // ignore
            }

            try {
                $table->dropUnique('experiment_measurements_session_id_feature_variant_unique');
            } catch (\Throwable) {
                // ignore
            }

            $table->unique(['session_id', 'feature', 'variant'], 'experiment_measurements_session_id_feature_variant_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('experiment_measurements')) {
            return;
        }

        Schema::connection($this->connection)->table('experiment_measurements', function (Blueprint $table): void {
            try {
                $table->dropUnique('experiment_measurements_session_id_feature_variant_unique');
            } catch (\Throwable) {
                // ignore
            }

            try {
                $table->dropUnique('experiment_measurements_session_id_feature_unique');
            } catch (\Throwable) {
                // ignore
            }

            $table->unique(['session_id', 'feature'], 'experiment_measurements_session_id_feature_unique');
        });
    }
}

