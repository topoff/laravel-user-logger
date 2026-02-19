<?php

namespace Topoff\LaravelUserLogger\Nova\Lenses;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Lenses\Lens;

class ExperimentResultsLens extends Lens
{
    /**
     * Get the query builder / paginator for the lens.
     */
    public static function query(LensRequest $request, Builder $query): Builder
    {
        $query = $query->select([
            'feature',
            DB::raw("COALESCE(variant, 'undefined') as variant"),
            DB::raw('COUNT(*) as sessions'),
            DB::raw('SUM(exposure_count) as exposures'),
            DB::raw('SUM(conversion_count) as conversions'),
            DB::raw('ROUND((SUM(conversion_count) / NULLIF(SUM(exposure_count), 0)) * 100, 2) as conversion_rate'),
        ])->groupBy('feature', 'variant');

        return $request->withOrdering(
            $request->withFilters($query),
            fn (Builder $query): Builder => $query->orderBy('feature')->orderBy('variant'),
        );
    }

    /**
     * Get the fields available to the lens.
     *
     * @return array<int, mixed>
     */
    public function fields(Request $request): array
    {
        return [
            Text::make('Feature', 'feature')->sortable(),
            Text::make('Variant', 'variant')->sortable(),
            Number::make('Sessions', 'sessions')->sortable(),
            Number::make('Exposures', 'exposures')->sortable(),
            Number::make('Conversions', 'conversions')->sortable(),
            Number::make('Conversion Rate', 'conversion_rate')->sortable(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function filters(Request $request): array
    {
        return [];
    }

    /**
     * @return array<int, mixed>
     */
    public function actions(Request $request): array
    {
        return [];
    }

    public function name(): string
    {
        return 'Results by Variant';
    }
}
