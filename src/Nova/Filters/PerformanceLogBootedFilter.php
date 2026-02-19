<?php

namespace Topoff\LaravelUserLogger\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class PerformanceLogBootedFilter extends Filter
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

        return $query->where('booted', $value === '1');
    }

    /**
     * Get the filter's available options.
     */
    public function options(NovaRequest $request): array
    {
        return [
            'Booted' => '1',
            'Not Booted' => '0',
        ];
    }
}
