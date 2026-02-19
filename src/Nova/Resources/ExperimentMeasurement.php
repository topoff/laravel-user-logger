<?php

namespace Topoff\LaravelUserLogger\Nova\Resources;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement as ExperimentMeasurementModel;
use Topoff\LaravelUserLogger\Nova\Compatibility\Resource;
use Topoff\LaravelUserLogger\Nova\Lenses\ExperimentResultsLens;

class ExperimentMeasurement extends Resource
{
    public $conversion_rate;

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<ExperimentMeasurementModel>
     */
    public static $model = ExperimentMeasurementModel::class;

    /**
     * The column that should be used to represent the resource.
     *
     * @var string
     */
    public static $title = 'feature';

    /**
     * The columns that should be searched.
     *
     * @var array<int, string>
     */
    public static $search = [
        'id',
        'session_id',
        'feature',
        'variant',
    ];

    /**
     * @var string
     */
    public static $group = 'User Logger';

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, mixed>
     */
    public function fields(Request $request): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Feature')->sortable(),
            Text::make('Variant')->sortable(),
            Text::make('Session', 'session_id')->sortable(),
            Number::make('Exposures', 'exposure_count')->sortable(),
            Number::make('Conversions', 'conversion_count')->sortable(),
            Number::make('Conversion Rate', fn (): float => $this->conversion_rate)->sortable(),
            DateTime::make('First Exposed', 'first_exposed_at')->sortable(),
            DateTime::make('Last Exposed', 'last_exposed_at')->sortable(),
            DateTime::make('First Converted', 'first_converted_at')->sortable(),
            DateTime::make('Last Converted', 'last_converted_at')->sortable(),
        ];
    }

    /**
     * Get the lenses available on the resource.
     *
     * @return array<int, mixed>
     */
    public function lenses(Request $request): array
    {
        return [
            new ExperimentResultsLens,
        ];
    }
}
