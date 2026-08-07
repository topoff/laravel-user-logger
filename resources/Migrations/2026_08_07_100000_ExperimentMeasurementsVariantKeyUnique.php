<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

/**
 * The unique index (session_id, feature, variant) never protected rows with
 * variant = NULL — MySQL and SQLite both allow any number of NULLs in a unique
 * index, so the parallel-insert race kept producing duplicate measurement rows
 * for NULL variants and the service's UniqueConstraintViolation recovery never
 * fired there. variant_key is the non-nullable stand-in ('' for NULL) that
 * closes the gap; existing duplicates are merged before the index is created.
 */
class ExperimentMeasurementsVariantKeyUnique extends Migration
{
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('experiment_measurements')) {
            return;
        }

        if (! Schema::connection($this->connection)->hasColumn('experiment_measurements', 'variant_key')) {
            Schema::connection($this->connection)->table('experiment_measurements', function (Blueprint $table): void {
                $table->string('variant_key', 120)->default('')->after('variant');
            });
        }

        DB::connection($this->connection)->table('experiment_measurements')
            ->update(['variant_key' => DB::raw("COALESCE(variant, '')")]);

        $this->mergeDuplicates();

        try {
            Schema::connection($this->connection)->table('experiment_measurements', function (Blueprint $table): void {
                $table->dropUnique('experiment_measurements_session_id_feature_variant_unique');
            });
        } catch (Throwable) {
            // ignore — index may not exist on this installation
        }

        try {
            Schema::connection($this->connection)->table('experiment_measurements', function (Blueprint $table): void {
                $table->unique(['session_id', 'feature', 'variant_key'], 'experiment_measurements_session_feature_variant_key_unique');
            });
        } catch (Throwable) {
            // ignore — already created
        }
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('experiment_measurements')) {
            return;
        }

        try {
            Schema::connection($this->connection)->table('experiment_measurements', function (Blueprint $table): void {
                $table->dropUnique('experiment_measurements_session_feature_variant_key_unique');
            });
        } catch (Throwable) {
            // ignore
        }

        if (Schema::connection($this->connection)->hasColumn('experiment_measurements', 'variant_key')) {
            Schema::connection($this->connection)->table('experiment_measurements', function (Blueprint $table): void {
                $table->dropColumn('variant_key');
            });
        }

        try {
            Schema::connection($this->connection)->table('experiment_measurements', function (Blueprint $table): void {
                $table->unique(['session_id', 'feature', 'variant'], 'experiment_measurements_session_id_feature_variant_unique');
            });
        } catch (Throwable) {
            // ignore
        }
    }

    /**
     * Merge duplicate (session_id, feature, variant_key) groups into their
     * oldest row: counts are summed, first_* timestamps take the minimum,
     * last_* the maximum, and the last_conversion_* details follow the row
     * with the most recent last_converted_at. Only NULL-variant rows can be
     * affected, so the volume is small.
     */
    protected function mergeDuplicates(): void
    {
        $duplicateGroups = DB::connection($this->connection)->table('experiment_measurements')
            ->selectRaw('session_id, feature, variant_key')
            ->groupBy('session_id', 'feature', 'variant_key')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $rows = DB::connection($this->connection)->table('experiment_measurements')
                ->where('session_id', $group->session_id)
                ->where('feature', $group->feature)
                ->where('variant_key', $group->variant_key)
                ->orderBy('id')
                ->get();

            $keeper = $rows->first();
            $latestConversion = $rows->whereNotNull('last_converted_at')->sortByDesc('last_converted_at')->first();

            DB::connection($this->connection)->table('experiment_measurements')
                ->where('id', $keeper->id)
                ->update([
                    'exposure_count' => (int) $rows->sum('exposure_count'),
                    'conversion_count' => (int) $rows->sum('conversion_count'),
                    'first_log_id' => $rows->whereNotNull('first_log_id')->min('first_log_id'),
                    'last_log_id' => $rows->whereNotNull('last_log_id')->max('last_log_id'),
                    'first_exposed_at' => $rows->whereNotNull('first_exposed_at')->min('first_exposed_at'),
                    'last_exposed_at' => $rows->whereNotNull('last_exposed_at')->max('last_exposed_at'),
                    'first_converted_at' => $rows->whereNotNull('first_converted_at')->min('first_converted_at'),
                    'last_converted_at' => $latestConversion->last_converted_at ?? null,
                    'last_conversion_event' => $latestConversion->last_conversion_event ?? null,
                    'last_conversion_entity_type' => $latestConversion->last_conversion_entity_type ?? null,
                    'last_conversion_entity_id' => $latestConversion->last_conversion_entity_id ?? null,
                ]);

            DB::connection($this->connection)->table('experiment_measurements')
                ->where('session_id', $group->session_id)
                ->where('feature', $group->feature)
                ->where('variant_key', $group->variant_key)
                ->where('id', '!=', $keeper->id)
                ->delete();
        }
    }
}
