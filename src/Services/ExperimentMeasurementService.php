<?php

namespace Topoff\LaravelUserLogger\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as LaravelLogger;
use Laravel\Pennant\Feature;
use Throwable;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Models\Log;
use Topoff\LaravelUserLogger\Models\Session;

class ExperimentMeasurementService
{
    public function setVariant(Session $session, string $feature, mixed $variant, ?Log $log = null): void
    {
        if (! $this->isMeasurementEnabled()) {
            return;
        }

        $this->activateFeature($session, $feature, $variant);

        $now = now();
        $normalizedVariant = $this->normalizeVariant($variant);
        $lastLogId = $log?->id;
        $measurement = $this->findMeasurement($session->id, $feature, $normalizedVariant);

        if ($measurement instanceof ExperimentMeasurement) {
            $this->touchVariantMeasurement($measurement, $lastLogId, $now);

            return;
        }

        try {
            ExperimentMeasurement::query()->create([
                'session_id' => $session->id,
                'feature' => $feature,
                'variant' => $normalizedVariant,
                'first_log_id' => $lastLogId,
                'last_log_id' => $lastLogId,
                // If no exposure row exists yet, create a baseline row for this request.
                'exposure_count' => 1,
                'conversion_count' => 0,
                'first_exposed_at' => $now,
                'last_exposed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Lost the insert race against a parallel request of the same
            // session (double navigation, prefetch): the row exists now, so
            // apply the update path instead of surfacing the violation.
            $measurement = $this->findMeasurement($session->id, $feature, $normalizedVariant);
            if ($measurement instanceof ExperimentMeasurement) {
                $this->touchVariantMeasurement($measurement, $lastLogId, $now);
            }
        }
    }

    protected function touchVariantMeasurement(ExperimentMeasurement $measurement, ?int $lastLogId, Carbon $now): void
    {
        if ($lastLogId !== null) {
            $measurement->last_log_id = $lastLogId;
        }
        $measurement->updated_at = $now;
        $measurement->save();
    }

    public function recordExposure(Session $session, Log $log): void
    {
        if (! $this->isMeasurementEnabled()) {
            return;
        }

        $features = $this->getTrackedFeatures();
        if ($features === []) {
            return;
        }

        $now = now();
        $existing = ExperimentMeasurement::query()
            ->where('session_id', $session->id)
            ->whereIn('feature', $features)
            ->get();

        foreach ($features as $feature) {
            $variant = $this->getVariant($feature, $session);
            $measurement = $existing->first(
                fn (ExperimentMeasurement $item): bool => $item->feature === $feature
                    && $this->variantsEqual($item->variant, $variant),
            );

            if ($measurement instanceof ExperimentMeasurement) {
                $this->touchExposureMeasurement($measurement, $log, $now);

                continue;
            }

            try {
                ExperimentMeasurement::query()->create([
                    'session_id' => $session->id,
                    'feature' => $feature,
                    'variant' => $variant,
                    'first_log_id' => $log->id,
                    'last_log_id' => $log->id,
                    'exposure_count' => 1,
                    'conversion_count' => 0,
                    'first_exposed_at' => $now,
                    'last_exposed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Same insert race as in setVariant(): a parallel request of
                // this session created the row after our prefetch — count the
                // exposure on the existing row instead of throwing.
                $measurement = $this->findMeasurement($session->id, $feature, $variant);
                if ($measurement instanceof ExperimentMeasurement) {
                    $this->touchExposureMeasurement($measurement, $log, $now);
                }
            }
        }
    }

    protected function touchExposureMeasurement(ExperimentMeasurement $measurement, Log $log, Carbon $now): void
    {
        $measurement->last_log_id = $log->id;
        $measurement->last_exposed_at = $now;
        $measurement->exposure_count++;
        $measurement->save();
    }

    public function recordConversion(Session $session, ?string $event = null, ?string $entityType = null, ?string $entityId = null, ?Log $log = null): void
    {
        if (! $this->isMeasurementEnabled() || ! $this->isConversionEvent($event, $entityType)) {
            return;
        }

        $now = now();
        $features = $this->getConversionFeatures($session);
        if ($features === []) {
            $this->applyConversion(
                ExperimentMeasurement::query()->where('session_id', $session->id),
                $now, $event, $entityType, $entityId, $log,
            );

            return;
        }

        foreach ($features as $feature) {
            $variant = $this->getVariant($feature, $session);
            $exactQuery = ExperimentMeasurement::query()
                ->where('session_id', $session->id)
                ->where('feature', $feature);

            if ($variant === null) {
                $exactQuery->whereNull('variant');
            } else {
                $exactQuery->where('variant', $variant);
            }

            if ($this->applyConversion($exactQuery, $now, $event, $entityType, $entityId, $log) > 0) {
                continue;
            }

            // Backward compatibility: no exact variant row yet - update only the
            // most recent row for the feature, never all variant rows at once.
            $fallbackQuery = ExperimentMeasurement::query()
                ->where('session_id', $session->id)
                ->where('feature', $feature)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->limit(1);

            $this->applyConversion($fallbackQuery, $now, $event, $entityType, $entityId, $log);
        }
    }

    /**
     * Apply conversion updates to all rows matched by the query.
     * Returns the number of updated rows.
     *
     * @param  Builder<ExperimentMeasurement>  $query
     */
    protected function applyConversion(Builder $query, Carbon $now, ?string $event, ?string $entityType, ?string $entityId, ?Log $log): int
    {
        (clone $query)->whereNull('first_converted_at')->update(['first_converted_at' => $now]);

        $updates = [
            'last_converted_at' => $now,
            'last_conversion_event' => $event,
            'last_conversion_entity_type' => $entityType,
            'last_conversion_entity_id' => $entityId,
            'conversion_count' => DB::raw('conversion_count + 1'),
            'updated_at' => $now,
        ];

        if ($log instanceof Log) {
            $updates['last_log_id'] = $log->id;
        }

        return $query->update($updates);
    }

    public function getVariant(string $feature, Session $session): ?string
    {
        try {
            $featureFacade = Feature::class;
            if (! class_exists($featureFacade)) {
                return null;
            }

            $store = (string) config('user-logger.experiments.pennant.store', 'user-logger');

            return $this->normalizeVariant($featureFacade::store($store)->for($this->resolveScope($session))->value($feature));
        } catch (Throwable $exception) {
            LaravelLogger::warning('Error resolving Pennant feature variant in topoff/user-logger: '.$exception->getMessage());

            return null;
        }
    }

    protected function activateFeature(Session $session, string $feature, mixed $variant): void
    {
        try {
            $featureFacade = Feature::class;
            if (! class_exists($featureFacade)) {
                return;
            }

            $store = (string) config('user-logger.experiments.pennant.store', 'user-logger');
            $featureFacade::store($store)->for($this->resolveScope($session))->activate($feature, $variant);
        } catch (Throwable $exception) {
            LaravelLogger::warning('Error activating Pennant feature variant in topoff/user-logger: '.$exception->getMessage());
        }
    }

    public function isVariant(string $feature, mixed $variant, Session $session): bool
    {
        return $this->getVariant($feature, $session) === $this->normalizeVariant($variant);
    }

    /**
     * @return list<string>
     */
    public function getTrackedFeatures(): array
    {
        return array_values(array_filter(
            config('user-logger.experiments.features', []),
            static fn ($feature): bool => is_string($feature) && $feature !== '',
        ));
    }

    /**
     * Features a conversion should be attributed to: the configured tracked
     * features plus any feature the session already holds an exposure row for.
     *
     * The latter covers on-read experiments whose flags are intentionally kept
     * out of config('user-logger.experiments.features') (so they are not
     * eagerly exposed on every request, but only recorded via setVariant() on
     * the pages that actually render them) — those flags must still receive
     * this session's conversions.
     *
     * @return list<string>
     */
    protected function getConversionFeatures(Session $session): array
    {
        $exposed = ExperimentMeasurement::query()
            ->where('session_id', $session->id)
            ->distinct()
            ->pluck('feature')
            ->filter(static fn ($feature): bool => is_string($feature) && $feature !== '')
            ->all();

        return array_values(array_unique([...$this->getTrackedFeatures(), ...$exposed]));
    }

    protected function isMeasurementEnabled(): bool
    {
        return config('user-logger.experiments.enabled', false) === true;
    }

    protected function isConversionEvent(?string $event = null, ?string $entityType = null): bool
    {
        if ($event === null || $event === '') {
            return false;
        }

        $conversionEvents = config('user-logger.experiments.conversion_events', ['conversion']);
        if (! in_array($event, $conversionEvents, true)) {
            return false;
        }

        $entityTypes = config('user-logger.experiments.conversion_entity_types', []);

        return $entityTypes === [] || in_array($entityType, $entityTypes, true);
    }

    protected function resolveScope(Session $session): mixed
    {
        if (config('user-logger.experiments.pennant.scope', 'session') === 'auth_or_session' && auth()->check()) {
            return auth()->user();
        }

        return 'user-logger-session:'.$session->id;
    }

    protected function normalizeVariant(mixed $variant): ?string
    {
        if ($variant === null) {
            return null;
        }

        if (is_bool($variant)) {
            return $variant ? 'true' : 'false';
        }

        if (is_scalar($variant)) {
            return (string) $variant;
        }

        return json_encode($variant, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }

    protected function findMeasurement(string $sessionId, string $feature, ?string $variant): ?ExperimentMeasurement
    {
        $query = ExperimentMeasurement::query()
            ->where('session_id', $sessionId)
            ->where('feature', $feature);

        if ($variant === null) {
            $query->whereNull('variant');
        } else {
            $query->where('variant', $variant);
        }

        return $query->first();
    }

    protected function variantsEqual(?string $left, ?string $right): bool
    {
        return $left === $right;
    }
}
