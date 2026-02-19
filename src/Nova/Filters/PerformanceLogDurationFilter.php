<?php

namespace Topoff\LaravelUserLogger\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class PerformanceLogDurationFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * The displayable name of the filter.
     *
     * @var string
     */
    public $name = 'Request Duration (ms)';

    /**
     * Apply the filter to the given query.
     *
     * @param  mixed  $query
     * @param  mixed  $value
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        return match ($value) {
            '<50' => $query->where('request_duration_ms', '<', 50),
            '50-200' => $query->whereBetween('request_duration_ms', [50, 200]),
            '200-1000' => $query->whereBetween('request_duration_ms', [200, 1000]),
            '1000+' => $query->where('request_duration_ms', '>=', 1000),
            default => $query,
        };
    }

    /**
     * Get the filter's available options.
     */
    public function options(NovaRequest $request): array
    {
        return [
            '< 50 ms' => '<50',
            '50-200 ms' => '50-200',
            '200-1000 ms' => '200-1000',
            '1000+ ms' => '1000+',
        ];
    }
}
