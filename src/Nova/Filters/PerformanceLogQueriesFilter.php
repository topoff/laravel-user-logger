<?php

namespace Topoff\LaravelUserLogger\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class PerformanceLogQueriesFilter extends Filter
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
    public $name = 'Queries';

    /**
     * Apply the filter to the given query.
     *
     * @param  mixed  $query
     * @param  mixed  $value
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        return match ($value) {
            'none' => $query->whereNull('queries_total'),
            '0' => $query->where('queries_total', 0),
            '1-10' => $query->whereBetween('queries_total', [1, 10]),
            '11-50' => $query->whereBetween('queries_total', [11, 50]),
            '51-200' => $query->whereBetween('queries_total', [51, 200]),
            '201+' => $query->where('queries_total', '>=', 201),
            default => $query,
        };
    }

    /**
     * Get the filter's available options.
     */
    public function options(NovaRequest $request): array
    {
        return [
            'None' => 'none',
            '0' => '0',
            '1-10' => '1-10',
            '11-50' => '11-50',
            '51-200' => '51-200',
            '201+' => '201+',
        ];
    }
}
