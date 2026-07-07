<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Support;

use Illuminate\Support\Collection;
use stdClass;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;

/**
 * Aggregates experiment measurements into per-variant conversion rates and runs
 * a two-proportion z-test on the session conversion rate of the two largest
 * variants. Shared by the Nova table metric and any other reporting surface.
 *
 * @phpstan-type VariantSummary array{variant: string, sessions: int, converting_sessions: int, conversions: int, exposures: int, cvr_per_session_pct: float, cvr_per_exposure_pct: float}
 */
class ExperimentStats
{
    /**
     * Per-feature summary keyed by feature name.
     *
     * @return array<string, array{variants: array<int, VariantSummary>, comparison: array<string, mixed>|null}>
     */
    public function summary(?string $feature = null): array
    {
        $rows = ExperimentMeasurement::query()->toBase()
            ->selectRaw("feature, COALESCE(NULLIF(variant, ''), '(none)') as variant")
            ->selectRaw('COUNT(*) as sessions')
            ->selectRaw('SUM(CASE WHEN conversion_count > 0 THEN 1 ELSE 0 END) as converting_sessions')
            ->selectRaw('SUM(conversion_count) as conversions')
            ->selectRaw('SUM(exposure_count) as exposures')
            ->when($feature !== null, fn ($query) => $query->where('feature', $feature))
            ->groupBy('feature', 'variant')
            ->get();

        return $rows
            ->groupBy('feature')
            ->map(fn (Collection $variants): array => $this->summariseFeature($variants))
            ->all();
    }

    /**
     * @param  Collection<int, stdClass>  $variants
     * @return array{variants: array<int, VariantSummary>, comparison: array<string, mixed>|null}
     */
    private function summariseFeature(Collection $variants): array
    {
        $summarised = $variants->map(function (stdClass $row): array {
            $sessions = (int) $row->sessions;
            $converters = (int) $row->converting_sessions;
            $exposures = (int) $row->exposures;

            return [
                'variant' => (string) $row->variant,
                'sessions' => $sessions,
                'converting_sessions' => $converters,
                'conversions' => (int) $row->conversions,
                'exposures' => $exposures,
                'cvr_per_session_pct' => $sessions > 0 ? round($converters / $sessions * 100, 2) : 0.0,
                'cvr_per_exposure_pct' => $exposures > 0 ? round((int) $row->conversions / $exposures * 100, 2) : 0.0,
            ];
        })->values();

        return [
            'variants' => $summarised->all(),
            'comparison' => $this->compareTopVariants($summarised),
        ];
    }

    /**
     * Two-proportion z-test on the session conversion rate of the two variants
     * with the most sessions. The lower-rate variant is the baseline so the
     * reported uplift is positive.
     *
     * @param  Collection<int, VariantSummary>  $variants
     * @return array<string, mixed>|null
     */
    public function compareTopVariants(Collection $variants): ?array
    {
        if ($variants->count() < 2) {
            return null;
        }

        $top = $variants->sortByDesc('sessions')->take(2)->values();
        [$a, $b] = [$top[0], $top[1]];

        $baseline = $a['cvr_per_session_pct'] <= $b['cvr_per_session_pct'] ? $a : $b;
        $challenger = $baseline === $a ? $b : $a;

        $n1 = $baseline['sessions'];
        $n2 = $challenger['sessions'];
        $c1 = $baseline['converting_sessions'];
        $c2 = $challenger['converting_sessions'];

        if ($n1 === 0 || $n2 === 0) {
            return null;
        }

        $p1 = $c1 / $n1;
        $p2 = $c2 / $n2;
        $pooled = ($c1 + $c2) / ($n1 + $n2);
        $se = sqrt($pooled * (1 - $pooled) * (1 / $n1 + 1 / $n2));

        $z = $se > 0.0 ? ($p2 - $p1) / $se : 0.0;

        return [
            'baseline' => $baseline['variant'],
            'challenger' => $challenger['variant'],
            'baseline_cvr_pct' => round($p1 * 100, 2),
            'challenger_cvr_pct' => round($p2 * 100, 2),
            'uplift_pct_points' => round(($p2 - $p1) * 100, 2),
            'relative_uplift_pct' => $p1 > 0.0 ? round(($p2 - $p1) / $p1 * 100, 2) : null,
            'z_score' => round($z, 3),
            'p_value' => round(2 * (1 - $this->normalCdf(abs($z))), 4),
            'significant_95' => abs($z) >= 1.96,
        ];
    }

    /**
     * Standard normal CDF via the Abramowitz & Stegun 7.1.26 erf approximation
     * (max abs error ~1.5e-7) — avoids a stats dependency for the z-test.
     */
    private function normalCdf(float $x): float
    {
        $t = 1 / (1 + 0.2316419 * abs($x));
        $d = 0.3989422804014327 * exp(-$x * $x / 2);
        $probability = $d * $t * (0.319381530 + $t * (-0.356563782 + $t * (1.781477937
            + $t * (-1.821255978 + $t * 1.330274429))));

        return $x >= 0 ? 1 - $probability : $probability;
    }
}
