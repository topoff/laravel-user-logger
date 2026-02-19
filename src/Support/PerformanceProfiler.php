<?php

namespace Topoff\LaravelUserLogger\Support;

class PerformanceProfiler
{
    /**
     * @var array<string, float>
     */
    protected array $segmentStarts = [];

    /**
     * @var array<string, float>
     */
    protected array $segmentDurations = [];

    /**
     * @var array<string, int>
     */
    protected array $counters = [];

    /**
     * @var array<string, mixed>
     */
    protected array $meta = [];

    public function start(string $segment): void
    {
        $this->segmentStarts[$segment] = microtime(true);
    }

    public function stop(string $segment): void
    {
        if (! array_key_exists($segment, $this->segmentStarts)) {
            return;
        }

        $durationMs = (microtime(true) - $this->segmentStarts[$segment]) * 1000;
        unset($this->segmentStarts[$segment]);

        $this->segmentDurations[$segment] = ($this->segmentDurations[$segment] ?? 0.0) + $durationMs;
    }

    public function setCounter(string $name, int $value): void
    {
        $this->counters[$name] = $value;
    }

    public function incrementCounter(string $name, int $amount = 1): void
    {
        $this->counters[$name] = ($this->counters[$name] ?? 0) + $amount;
    }

    public function setMeta(string $name, mixed $value): void
    {
        $this->meta[$name] = $value;
    }

    /**
     * @return array{segments: array<string, float>, counters: array<string, int>, meta: array<string, mixed>}
     */
    public function snapshot(): array
    {
        $segments = [];
        foreach ($this->segmentDurations as $key => $value) {
            $segments[$key] = round($value, 3);
        }

        return [
            'segments' => $segments,
            'counters' => $this->counters,
            'meta' => $this->meta,
        ];
    }
}
