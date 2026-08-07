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
 * before the index is created.
 *
 * variant_key is a DATABASE-GENERATED (virtual) column on purpose: rows
 * written by still-running pre-v10.9 workers during a rolling deploy carry no
 * application-side knowledge of the encoding — a plain column with a ''
 * default would collapse their distinct variants into one merge group. The
 * database deriving the value makes every writer correct by construction.
 * (VIRTUAL, not STORED: SQLite cannot ALTER TABLE ADD a stored generated
 * column, and both MySQL and SQLite support unique indexes on virtual ones.)
 *
 * Deploy note: preferably run this while experiment writes are quiesced
 * (maintenance mode / paused workers). An un-quiesced run cannot corrupt data:
 * the old unique index stays in place until the new one is verified, each
 * merge group is processed in a lockForUpdate transaction, and the service
 * counts exposures as atomic relative increments that re-target the canonical
 * row when the loaded one was merged away. At worst a single increment is
 * lost if the canonical row vanishes again in that same instant.
 */
class ExperimentMeasurementsVariantKeyUnique extends Migration
{
    private const string UNIQUE_INDEX = 'experiment_measurements_session_feature_variant_key_unique';

    private const string LEGACY_UNIQUE_INDEX = 'experiment_measurements_session_id_feature_variant_unique';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('experiment_measurements')) {
            return;
        }

        if (! $schema->hasColumn('experiment_measurements', 'variant_key')) {
            $expression = $this->variantKeyExpression();
            $schema->table('experiment_measurements', function (Blueprint $table) use ($expression): void {
                // variant is 120 chars; the 'v' prefix needs one more.
                $table->string('variant_key', 121)->virtualAs($expression)->after('variant');
            });
        }

        if (! $schema->hasIndex('experiment_measurements', self::UNIQUE_INDEX, 'unique')) {
            $attempts = 0;
            while (true) {
                // Re-run the dedupe immediately before every index attempt:
                // under live traffic a fresh NULL-duplicate can appear between
                // the merge and CREATE UNIQUE INDEX. The creation failure is
                // deliberately NOT swallowed — the old unique index is only
                // dropped further below once this one is verified, so a failed
                // run leaves the table with its old protection intact.
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

        if (! $schema->hasIndex('experiment_measurements', self::UNIQUE_INDEX, 'unique')) {
            throw new RuntimeException('Unique index '.self::UNIQUE_INDEX.' was not created on experiment_measurements.');
        }

        // Only after the new index is in place and verified may the old one
        // go — if anything above threw, the table keeps its old protection.
        try {
            $schema->table('experiment_measurements', function (Blueprint $table): void {
                $table->dropUnique(self::LEGACY_UNIQUE_INDEX);
            });
        } catch (Throwable) {
            // ignore — index may not exist on this installation
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('experiment_measurements')) {
            return;
        }

        // Mirror of up(): restore the old protection — failing loudly if that
        // does not work — before the new index and the column are removed, so
        // a broken rollback never leaves the table without any unique index.
        if (! $schema->hasIndex('experiment_measurements', self::LEGACY_UNIQUE_INDEX, 'unique')) {
            $schema->table('experiment_measurements', function (Blueprint $table): void {
                $table->unique(['session_id', 'feature', 'variant'], self::LEGACY_UNIQUE_INDEX);
            });
        }

        if (! $schema->hasIndex('experiment_measurements', self::LEGACY_UNIQUE_INDEX, 'unique')) {
            throw new RuntimeException('Unique index '.self::LEGACY_UNIQUE_INDEX.' was not restored on experiment_measurements.');
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
    }

    /**
     * The injective encoding as a generated-column expression: '' for NULL,
     * 'v'.value for real variants. Driver-specific because string
     * concatenation differs (MySQL CONCAT vs. SQLite ||).
     */
    protected function variantKeyExpression(): string
    {
        return DB::connection($this->connection)->getDriverName() === 'sqlite'
            ? "case when variant is null then '' else 'v' || variant end"
            : "case when variant is null then '' else concat('v', variant) end";
    }

    /**
     * Merge duplicate (session_id, feature, variant_key) groups into their
     * oldest row: counts are summed, first_* timestamps take the minimum,
     * last_* the maximum, and the last_conversion_* details follow the row
     * with the most recent last_converted_at. Only NULL-variant rows can be
     * affected (real variants were unique before), so the volume is small.
     *
     * Each group runs in its own transaction with the rows locked FOR UPDATE:
     * concurrent recordExposure()/recordConversion() writes on these rows
     * block until the merge committed, so they cannot interleave between the
     * read, the keeper update and the delete. Deletion targets the locked ids
     * explicitly — rows inserted after the locked read are left for the
     * retry loop around the index creation.
     */
    protected function mergeDuplicates(): void
    {
        $duplicateGroups = DB::connection($this->connection)->table('experiment_measurements')
            ->selectRaw('session_id, feature, variant_key')
            ->groupBy('session_id', 'feature', 'variant_key')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            DB::connection($this->connection)->transaction(function () use ($group): void {
                $rows = DB::connection($this->connection)->table('experiment_measurements')
                    ->where('session_id', $group->session_id)
                    ->where('feature', $group->feature)
                    ->where('variant_key', $group->variant_key)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($rows->count() < 2) {
                    return;
                }

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
                    ->whereIn('id', $rows->skip(1)->pluck('id')->all())
                    ->delete();
            });
        }
    }
}
