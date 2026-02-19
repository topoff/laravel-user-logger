<?php

namespace Topoff\LaravelUserLogger\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as LaravelLogger;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Models\Log;
use Topoff\LaravelUserLogger\Models\Session;
use Throwable;

class ExperimentMeasurementService
{
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
            ->get()
            ->keyBy('feature');

        $insertRows = [];

        foreach ($features as $feature) {
            $variant = $this->getVariant($feature, $session);
            $measurement = $existing->get($feature);

            if ($measurement instanceof ExperimentMeasurement) {
                $measurement->variant = $variant;
                $measurement->last_log_id = $log->id;
                $measurement->last_exposed_at = $now;
                $measurement->exposure_count++;
                $measurement->save();

                continue;
            }

            $insertRows[] = [
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
            ];
        }

        if ($insertRows !== []) {
            ExperimentMeasurement::query()->insert($insertRows);
        }
    }

    public function recordConversion(Session $session, ?string $event = null, ?string $entityType = null, ?string $entityId = null, ?Log $log = null): void
    {
        if (! $this->isMeasurementEnabled() || ! $this->isConversionEvent($event, $entityType)) {
            return;
        }

        $query = ExperimentMeasurement::query()->where('session_id', $session->id);
        if (! $query->exists()) {
            return;
        }

        $now = now();
        $nowString = $now->format('Y-m-d H:i:s');
        $updates = [
            'first_converted_at' => DB::raw("COALESCE(first_converted_at, '{$nowString}')"),
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

        $query->update($updates);
    }

    public function getVariant(string $feature, Session $session): ?string
    {
        try {
            $featureFacade = '\\Laravel\\Pennant\\Feature';
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
}
