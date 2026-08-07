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
 * fired there. variant_key is the non-nullable, injective stand-in ('' for
 * NULL, 'v'.value for real variants — a genuine '' variant maps to 'v' and can
 * never collide with NULL); existing duplicates are merged per variant_key
 * before the index is created. The encoding must match
 * ExperimentMeasurement::variantKeyFor().
 */
class ExperimentMeasurementsVariantKeyUnique extends Migration
{
    private const string UNIQUE_INDEX = 'experiment_measurements_session_feature_variant_key_unique';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('experiment_measurements')) {
            return;
        }

        if (! $schema->hasColumn('experiment_measurements', 'variant_key')) {
            $schema->table('experiment_measurements', function (Blueprint $table): void {
                // variant is 120 chars; the 'v' prefix needs one more.
                $table->string('variant_key', 121)->default('')->after('variant');
            });
        }

        $this->backfillVariantKeys();

        try {
            $schema->table('experiment_measurements', function (Blueprint $table): void {
                $table->dropUnique('experiment_measurements_session_id_feature_variant_unique');
            });
        } catch (Throwable) {
            // ignore — index may not exist on this installation
        }

        if (! $schema->hasIndex('experiment_measurements', self::UNIQUE_INDEX)) {
            $attempts = 0;
            while (true) {
                // Re-run the dedupe immediately before every index attempt:
                // under live traffic a fresh NULL-duplicate can appear between
                // the merge and CREATE UNIQUE INDEX. The creation failure is
                // deliberately NOT swallowed — a silently missing index would
                // leave the table unprotected with the old index already gone.
                $this->mergeDuplicates();

                try {
                    $schema->table('experiment_measurements', function (Blueprint $table): void {
                        $table->unique(['session_id', 'feature', 'variant_key'], self::UNIQUE_INDEX);
                    });

                    break;
                } catch (Throwable $exception) {
                    if (++$attempts >= 3) {
                        throw $exception;
                    }
                }
            }
        }

        if (! $schema->hasIndex('experiment_measurements', self::UNIQUE_INDEX)) {
            throw new RuntimeException('Unique index '.self::UNIQUE_INDEX.' was not created on experiment_measurements.');
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('experiment_measurements')) {
            return;
        }

        try {
            $schema->table('experiment_measurements', function (Blueprint $table): void {
                $table->dropUnique(self::UNIQUE_INDEX);
            });
        } catch (Throwable) {
            // ignore
        }

        if ($schema->hasColumn('experiment_measurements', 'variant_key')) {
            $schema->table('experiment_measurements', function (Blueprint $table): void {
                $table->dropColumn('variant_key');
            });
        }

        try {
            $schema->table('experiment_measurements', function (Blueprint $table): void {
                $table->unique(['session_id', 'feature', 'variant'], 'experiment_measurements_session_id_feature_variant_unique');
            });
        } catch (Throwable) {
            // ignore
        }
    }

    /**
     * SQL mirror of ExperimentMeasurement::variantKeyFor(): '' for NULL,
     * 'v'.value for real variants. Two driver-specific bulk updates instead of
     * one CASE expression because string concatenation differs (MySQL CONCAT
     * vs. SQLite ||).
     */
    protected function backfillVariantKeys(): void
    {
        $connection = DB::connection($this->connection);

        $connection->table('experiment_measurements')
            ->whereNull('variant')
            ->update(['variant_key' => '']);

        $prefixedVariant = $connection->getDriverName() === 'sqlite' ? "'v' || variant" : "CONCAT('v', variant)";
        $connection->table('experiment_measurements')
            ->whereNotNull('variant')
            ->update(['variant_key' => DB::raw($prefixedVariant)]);
    }

    /**
     * Merge duplicate (session_id, feature, variant_key) groups into their
     * oldest row: counts are summed, first_* timestamps take the minimum,
     * last_* the maximum, and the last_conversion_* details follow the row
     * with the most recent last_converted_at. Only NULL-variant rows can be
     * affected (real variants were unique before), so the volume is small.
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
