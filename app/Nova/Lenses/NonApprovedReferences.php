<?php

namespace App\Nova\Lenses;

use App\Nova\Actions\MarkReferenceSaved;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;

class NonApprovedReferences extends Lens
{
    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [];

    /**
     * Get the query builder / paginator for the lens.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return mixed
     */
    public static function query(LensRequest $request, $query)
    {
        return $request->withOrdering($request->withFilters(
            $query->where('approval_status', 'received')
        ));
    }

    /**
     * Get the fields available to the lens.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('Reference')->sortable(),
            Text::make('Name'),
            Badge::make('Status')->map([
                'active' => 'success',
                'deleted' => 'danger',
                'proposed' => 'info',
            ]),
            Badge::make('Approval status')->map([
                'received' => 'info',
                'declined' => 'danger',
                'approved' => 'success',
                'saved' => 'warning',
            ])->required(),

            Number::make('Latitude', fn () => $this->location->getLat())->copyable(), /** @phpstan-ignore-line */
            Number::make('Longitude', fn () => $this->location->getLng())->copyable(), /** @phpstan-ignore-line */
            URL::make('Protected Planet', fn () => 'https://www.protectedplanet.net/'.$this->wdpa_id)->displayUsing(fn () => 'Link'), /** @phpstan-ignore-line */
        ];
    }

    /**
     * Get the cards available on the lens.
     *
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the lens.
     *
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available on the lens.
     *
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [
            new MarkReferenceSaved,
        ];
    }

    /**
     * Get the URI key for the lens.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'non-approved-references';
    }
}
