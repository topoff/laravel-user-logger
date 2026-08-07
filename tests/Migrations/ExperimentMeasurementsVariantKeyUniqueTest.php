<?php

namespace Topoff\LaravelUserLogger\Tests\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../../resources/Migrations/2026_08_07_100000_ExperimentMeasurementsVariantKeyUnique.php';

class ExperimentMeasurementsVariantKeyUniqueTest extends TestCase
{
    /**
     * Rebuild the pre-migration state: no variant_key column, old unique on
     * the nullable variant column (which allows NULL duplicates).
     */
    protected function setUp(): void
    {
        parent::setUp();

        Schema::connection('user-logger')->table('experiment_measurements', function (Blueprint $table): void {
            $table->dropUnique('experiment_measurements_session_feature_variant_key_unique');
        });
        Schema::connection('user-logger')->table('experiment_measurements', function (Blueprint $table): void {
            $table->dropColumn('variant_key');
        });
        Schema::connection('user-logger')->table('experiment_measurements', function (Blueprint $table): void {
            $table->unique(['session_id', 'feature', 'variant'], 'experiment_measurements_session_id_feature_variant_unique');
        });
    }

    public function test_migration_merges_null_variant_duplicates_and_enforces_the_new_unique_index(): void
    {
        $insert = fn (array $row) => DB::connection('user-logger')->table('experiment_measurements')->insert($row + [
            'session_id' => '00000000-0000-0000-0000-00000000000a',
            'feature' => 'which-landingpage',
            'variant' => null,
        ]);

        // The NULL-duplicate pair the old index could not prevent …
        $insert([
            'exposure_count' => 3, 'conversion_count' => 1,
            'first_log_id' => 10, 'last_log_id' => 20,
            'first_exposed_at' => '2026-08-01 10:00:00', 'last_exposed_at' => '2026-08-02 10:00:00',
            'first_converted_at' => '2026-08-01 12:00:00', 'last_converted_at' => '2026-08-01 12:00:00',
            'last_conversion_event' => 'conversion-old',
        ]);
        $insert([
            'exposure_count' => 2, 'conversion_count' => 2,
            'first_log_id' => 15, 'last_log_id' => 30,
            'first_exposed_at' => '2026-08-01 09:00:00', 'last_exposed_at' => '2026-08-03 10:00:00',
            'first_converted_at' => '2026-08-02 12:00:00', 'last_converted_at' => '2026-08-02 12:00:00',
            'last_conversion_event' => 'conversion-new',
        ]);
        // … an unrelated real variant that must stay untouched …
        $insert(['variant' => 'b', 'exposure_count' => 7, 'conversion_count' => 0]);
        // … and a genuine ''-variant, which must NOT be merged into the NULL
        // group (injective encoding: NULL → '', '' → 'v').
        $insert(['variant' => '', 'exposure_count' => 9, 'conversion_count' => 0]);

        (new \ExperimentMeasurementsVariantKeyUnique)->up();

        $rows = ExperimentMeasurement::query()
            ->where('session_id', '00000000-0000-0000-0000-00000000000a')
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $rows);

        $merged = $rows->first(fn (ExperimentMeasurement $row): bool => $row->variant === null);
        $this->assertSame('', $merged->variant_key);
        $this->assertSame(5, $merged->exposure_count);
        $this->assertSame(3, $merged->conversion_count);
        $this->assertSame(10, $merged->first_log_id);
        $this->assertSame(30, $merged->last_log_id);
        $this->assertSame('2026-08-01 09:00:00', $merged->first_exposed_at->toDateTimeString());
        $this->assertSame('2026-08-03 10:00:00', $merged->last_exposed_at->toDateTimeString());
        $this->assertSame('2026-08-01 12:00:00', $merged->first_converted_at->toDateTimeString());
        $this->assertSame('conversion-new', $merged->last_conversion_event);

        $untouched = $rows->firstWhere('variant', 'b');
        $this->assertSame('vb', $untouched->variant_key);
        $this->assertSame(7, $untouched->exposure_count);

        $emptyStringVariant = $rows->first(fn (ExperimentMeasurement $row): bool => $row->variant === '');
        $this->assertSame('v', $emptyStringVariant->variant_key);
        $this->assertSame(9, $emptyStringVariant->exposure_count);

        // The new index must now reject exactly the duplicate the old one allowed.
        $this->expectException(UniqueConstraintViolationException::class);
        DB::connection('user-logger')->table('experiment_measurements')->insert([
            'session_id' => '00000000-0000-0000-0000-00000000000a',
            'feature' => 'which-landingpage',
            'variant' => null,
            'variant_key' => '',
        ]);
    }
}
