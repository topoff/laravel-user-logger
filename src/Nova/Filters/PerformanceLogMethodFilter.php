<?php

namespace Topoff\LaravelUserLogger\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Topoff\LaravelUserLogger\Models\PerformanceLog;

class PerformanceLogMethodFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * Apply the filter to the given query.
     *
     * @param  mixed  $query
     * @param  mixed  $value
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        if ($value === null || $value === '') {
            return $query;
        }

        return $query->where('method', $value);
    }

    /**
     * Get the filter's available options.
     */
    public function options(NovaRequest $request): array
    {
        $methods = PerformanceLog::query()
            ->whereNotNull('method')
            ->distinct()
            ->orderBy('method')
            ->pluck('method')
            ->all();

        return array_combine($methods, $methods) ?: [];
    }
}
