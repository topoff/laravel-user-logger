<?php

namespace Topoff\LaravelUserLogger\Nova\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Topoff\LaravelUserLogger\Models\PerformanceLog as PerformanceLogModel;
use Topoff\LaravelUserLogger\Nova\Compatibility\Resource;
use Topoff\LaravelUserLogger\Nova\Filters\PerformanceLogBootedFilter;
use Topoff\LaravelUserLogger\Nova\Filters\PerformanceLogDurationFilter;
use Topoff\LaravelUserLogger\Nova\Filters\PerformanceLogMethodFilter;
use Topoff\LaravelUserLogger\Nova\Filters\PerformanceLogQueriesFilter;
use Topoff\LaravelUserLogger\Nova\Filters\PerformanceLogSkipReasonFilter;
use Topoff\LaravelUserLogger\Nova\Filters\PerformanceLogStatusFilter;

class PerformanceLog extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<PerformanceLogModel>
     */
    public static $model = PerformanceLogModel::class;

    /**
     * The column that should be used to represent the resource.
     *
     * @var string
     */
    public static $title = 'path';

    /**
     * The columns that should be searched.
     *
     * @var array<int, string>
     */
    public static $search = [
        'id',
        'log_id',
        'domain_id',
        'path',
        'method',
        'status',
        'skip_reason',
    ];

    /**
     * @var string
     */
    public static $group = 'User Logger';

    /**
     * @return array<int, mixed>
     */
    public function fields(Request $request): array
    {
        return [
            ID::make()->sortable(),
            DateTime::make('Created At', 'created_at')->sortable(),
            Text::make('Path')->sortable(),
            Text::make('Method')->sortable(),
            Number::make('Status', 'status')->sortable(),
            Number::make('Log ID', 'log_id')->sortable(),
            Number::make('Domain ID', 'domain_id')->sortable(),
            Boolean::make('Booted', 'booted')->sortable(),
            Text::make('Skip Reason', 'skip_reason')->sortable(),
            Number::make('Request Duration (ms)', 'request_duration_ms')->sortable(),
            Number::make('Boot Duration (ms)', 'boot_duration_ms')->sortable(),
            Number::make('Queries Total', 'queries_total')->sortable(),
            Number::make('Queries User Logger', 'queries_user_logger')->sortable(),
            Text::make('User Logger Segments', fn (): ?string => $this->toJsonString($this->user_logger_segments))->onlyOnDetail(),
            Text::make('User Logger Counters', fn (): ?string => $this->toJsonString($this->user_logger_counters))->onlyOnDetail(),
            Text::make('User Logger Meta', fn (): ?string => $this->toJsonString($this->user_logger_meta))->onlyOnDetail(),
        ];
    }

    /**
     * @param  array<mixed>|null  $value
     */
    protected function toJsonString(?array $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: null;
    }

    /**
     * Keep newest entries first by default.
     */
    public static function indexQuery(Request $request, $query): Builder
    {
        return $query->orderByDesc('id');
    }

    /**
     * @return array<int, mixed>
     */
    public function filters(Request $request): array
    {
        return [
            new PerformanceLogMethodFilter,
            new PerformanceLogStatusFilter,
            new PerformanceLogQueriesFilter,
            new PerformanceLogDurationFilter,
            new PerformanceLogBootedFilter,
            new PerformanceLogSkipReasonFilter,
        ];
    }
}
