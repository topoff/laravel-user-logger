<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Tests\Support;

use Illuminate\Support\Str;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Models\Session;
use Topoff\LaravelUserLogger\Support\ExperimentStats;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class ExperimentStatsTest extends TestCase
{
    private function seedVariant(string $variant, int $sessions, int $converters): void
    {
        for ($i = 0; $i < $sessions; $i++) {
            ExperimentMeasurement::create([
                'session_id' => (string) Str::uuid(),
                'feature' => 'homepage-trustsignals',
                'variant' => $variant,
                'exposure_count' => 1,
                'conversion_count' => $i < $converters ? 1 : 0,
            ]);
        }
    }

    public function test_summary_computes_session_conversion_rate_per_variant(): void
    {
        $this->seedVariant('control', sessions: 40, converters: 2);
        $this->seedVariant('badges', sessions: 40, converters: 12);

        $summary = (new ExperimentStats)->summary('homepage-trustsignals');

        $this->assertArrayHasKey('homepage-trustsignals', $summary);

        $variants = collect($summary['homepage-trustsignals']['variants'])->keyBy('variant');

        $this->assertSame(5.0, $variants['control']['cvr_per_session_pct']);
        $this->assertSame(30.0, $variants['badges']['cvr_per_session_pct']);
        $this->assertSame(2, $variants['control']['converting_sessions']);
        $this->assertSame(12, $variants['badges']['converting_sessions']);
    }

    public function test_comparison_flags_a_significant_difference(): void
    {
        $this->seedVariant('control', sessions: 40, converters: 2);
        $this->seedVariant('badges', sessions: 40, converters: 12);

        $comparison = (new ExperimentStats)->summary('homepage-trustsignals')['homepage-trustsignals']['comparison'];

        $this->assertNotNull($comparison);
        $this->assertSame('control', $comparison['baseline']);
        $this->assertSame('badges', $comparison['challenger']);
        $this->assertSame(25.0, $comparison['uplift_pct_points']);
        $this->assertTrue($comparison['significant_95']);
    }

    public function test_comparison_is_null_for_a_single_variant(): void
    {
        $this->seedVariant('control', sessions: 10, converters: 1);

        $comparison = (new ExperimentStats)->summary('homepage-trustsignals')['homepage-trustsignals']['comparison'];

        $this->assertNull($comparison);
    }

    private function seedSession(string $variant, bool $robot, bool $converted, string $feature = 'homepage-trustsignals', bool $withSessionRow = true): void
    {
        $sessionId = (string) Str::uuid();
        if ($withSessionRow) {
            Session::query()->create(['id' => $sessionId, 'is_robot' => $robot]);
        }

        ExperimentMeasurement::create([
            'session_id' => $sessionId,
            'feature' => $feature,
            'variant' => $variant,
            'exposure_count' => 2,
            'conversion_count' => $converted ? 1 : 0,
        ]);
    }

    public function test_summary_ignores_robot_sessions_in_every_figure(): void
    {
        $this->seedSession('control', robot: false, converted: true);
        $this->seedSession('control', robot: false, converted: false);
        $this->seedSession('control', robot: true, converted: false);
        $this->seedSession('control', robot: true, converted: false);
        $this->seedSession('badges', robot: false, converted: false);
        $this->seedSession('badges', robot: false, converted: true, withSessionRow: false);

        $variants = collect((new ExperimentStats)->summary('homepage-trustsignals')['homepage-trustsignals']['variants'])->keyBy('variant');

        $this->assertSame(2, $variants['control']['sessions']);
        $this->assertSame(1, $variants['control']['converting_sessions']);
        $this->assertSame(4, $variants['control']['exposures']);
        $this->assertSame(50.0, $variants['control']['cvr_per_session_pct']);
        $this->assertSame(2, $variants['badges']['sessions'], 'a measurement without session row (pruned) still counts');
    }

    public function test_robots_are_ignored_even_when_robots_are_logged(): void
    {
        config()->set('user-logger.log_robots', true);
        $this->seedSession('control', robot: false, converted: false);
        $this->seedSession('control', robot: true, converted: false);

        $variants = collect((new ExperimentStats)->summary('homepage-trustsignals')['homepage-trustsignals']['variants'])->keyBy('variant');

        $this->assertSame(1, $variants['control']['sessions']);
    }

    public function test_a_variant_of_only_robots_disappears_and_leaves_no_comparison(): void
    {
        $this->seedSession('control', robot: false, converted: false);
        $this->seedSession('badges', robot: true, converted: true);
        $this->seedSession('bots-only', robot: true, converted: false, feature: 'other-feature');

        $summary = (new ExperimentStats)->summary();

        $this->assertArrayNotHasKey('other-feature', $summary);
        $this->assertSame(['control'], collect($summary['homepage-trustsignals']['variants'])->pluck('variant')->all());
        $this->assertNull($summary['homepage-trustsignals']['comparison']);
    }
}
