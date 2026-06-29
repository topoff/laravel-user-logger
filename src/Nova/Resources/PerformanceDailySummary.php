<?php

namespace Topoff\LaravelUserLogger\Nova\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Topoff\LaravelUserLogger\Models\PerformanceDailySummary as PerformanceDailySummaryModel;
use Topoff\LaravelUserLogger\Nova\Metrics\ConversionsPerDay;
use Topoff\LaravelUserLogger\Nova\Metrics\Errors5xxPerDay;
use Topoff\LaravelUserLogger\Nova\Metrics\P95LatencyPerDay;
use Topoff\LaravelUserLogger\Nova\Metrics\RequestsPerDay;

class PerformanceDailySummary extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<PerformanceDailySummaryModel>
     */
    public static $model = PerformanceDailySummaryModel::class;

    /**
     * The column that should be used to represent the resource.
     *
     * @var string
     */
    public static $title = 'summary_date';

    /**
     * The columns that should be searched.
     *
     * @var array<int, string>
     */
    public static $search = [
        'summary_date',
    ];

    /**
     * @var string
     */
    public static $group = 'User Logger';

    public static function label(): string
    {
        return 'Performance Daily Summaries';
    }

    public static function singularLabel(): string
    {
        return 'Performance Daily Summary';
    }

    /**
     * @return array<int, mixed>
     */
    public function fields(Request $request): array
    {
        return [
            ID::make()->sortable(),
            Date::make('Date', 'summary_date')->sortable(),

            Number::make('Requests')->sortable(),
            Number::make('Sample Rate', 'sample_rate')->onlyOnDetail(),

            Number::make('Avg ms', 'avg_duration_ms')->sortable(),
            Number::make('p50 ms', 'p50_duration_ms')->sortable(),
            Number::make('p95 ms', 'p95_duration_ms')->sortable(),
            Number::make('p99 ms', 'p99_duration_ms')->onlyOnDetail(),
            Number::make('Max ms', 'max_duration_ms')->onlyOnDetail(),
            Number::make('Slow Requests', 'slow_requests')->sortable(),

            Number::make('4xx', 'errors_4xx')->sortable(),
            Number::make('5xx', 'errors_5xx')->sortable(),

            Number::make('Cold Boots', 'cold_boots')->onlyOnDetail(),
            Number::make('Avg Boot ms', 'avg_boot_duration_ms')->onlyOnDetail(),
            Number::make('Avg Queries', 'avg_queries')->onlyOnDetail(),
            Number::make('Max Queries', 'max_queries')->onlyOnDetail(),

            Number::make('Sessions')->sortable(),
            Number::make('Conversions')->sortable(),
            Number::make('Conv. Rate %', 'conversion_rate')
                ->displayUsing(fn (?float $value): ?string => $value === null ? null : round($value * 100, 2).' %')
                ->sortable(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function cards(NovaRequest $request): array
    {
        return [
            ConversionsPerDay::make(),
            P95LatencyPerDay::make(),
            Errors5xxPerDay::make(),
            RequestsPerDay::make(),
        ];
    }

    /**
     * Newest day first by default.
     */
    public static function indexQuery(NovaRequest $request, $query): Builder
    {
        return $query->orderByDesc('summary_date');
    }

    /**
     * Generated data - never created/edited through Nova.
     */
    public static function authorizedToCreate(Request $request): bool
    {
        return false;
    }

    public function authorizedToUpdate(Request $request): bool
    {
        return false;
    }

    public function authorizedToReplicate(Request $request): bool
    {
        return false;
    }

    public function authorizedToDelete(Request $request): bool
    {
        return false;
    }
}
