<?php

namespace App\Nova;

use Ghanem\GoogleMap\GHMap;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class Reference extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Reference>
     */
    public static $model = \App\Models\Reference::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'reference',
        'name',
    ];

    /**
     * Get the value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function title()
    {
        return $this->reference;
    }

    /**
     * Get the search result subtitle for the resource.
     *
     * @return string
     */
    public function subtitle()
    {
        return $this->name;
    }

    public static function authorizedToCreate(Request $request)
    {
        return false;
    }

    public function authorizedToDelete(Request $request)
    {
        return false;
    }

    public function authorizedToReplicate(Request $request)
    {
        return false;
    }

    /**
     * Get the fields displayed by the resource.
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

            Number::make('Latitude', fn () => $this->location->getLat()),
            Number::make('Longitude', fn () => $this->location->getLng()),
            Boolean::make('Natura 2000 area', 'natura_2000_area'),
            URL::make('Protected Planet', fn () => 'https://www.protectedplanet.net/'.$this->wdpa_id)->displayUsing(fn () => 'Link'),
            URL::make('WWFF', fn () => 'https://wwff.co/directory/?showRef='.$this->reference)->displayUsing(fn () => 'Link'),

            new Panel('Map', [
                GHMap::make('Map')->latitude(optional($this->location)->getLat())->longitude(optional($this->location)->getLng())->hideFromIndex(),
            ]),
        ];
    }

    /**
     * Get the fields displayed by the resource on detail page.
     *
     * @return array
     */
    public function fieldsForUpdate(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('Reference')->readonly(),
            Text::make('Name')->readonly(),
            Select::make('Approval status')->options([
                'received' => 'Received',
                'declined' => 'Declined',
                'approved' => 'Approved',
                'saved' => 'Saved',
            ])->onlyOnForms(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [
            new Filters\ReferenceStatus,
            new Filters\ReferenceApprovalStatus,
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }
}
