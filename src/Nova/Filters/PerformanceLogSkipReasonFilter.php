<?php

namespace Topoff\LaravelUserLogger\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Topoff\LaravelUserLogger\Models\PerformanceLog;

class PerformanceLogSkipReasonFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    protected string $noneOptionValue = '__none__';

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

        if ($value === $this->noneOptionValue) {
            return $query->whereNull('skip_reason');
        }

        return $query->where('skip_reason', $value);
    }

    /**
     * Get the filter's available options.
     */
    public function options(NovaRequest $request): array
    {
        $reasons = PerformanceLog::query()
            ->whereNotNull('skip_reason')
            ->distinct()
            ->orderBy('skip_reason')
            ->pluck('skip_reason')
            ->all();

        $options = [];
        foreach ($reasons as $reason) {
            $options[(string) $reason] = (string) $reason;
        }

        $options['None'] = $this->noneOptionValue;

        return $options;
    }
}
