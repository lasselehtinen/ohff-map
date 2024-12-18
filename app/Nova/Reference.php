<?php

namespace App\Nova;

use App\Nova\Actions\MarkReferenceSaved;
use Ghanem\GoogleMap\GHMap;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use SimpleSquid\Nova\Fields\AdvancedNumber\AdvancedNumber;

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
        return $this->reference; /** @phpstan-ignore-line */
    }

    /**
     * Get the search result subtitle for the resource.
     *
     * @return string
     */
    public function subtitle()
    {
        return $this->name; /** @phpstan-ignore-line */
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
            Text::make('County'),
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

            AdvancedNumber::make('Latitude', fn () => $this->location->getLat())->decimals(5)->copyable(), /** @phpstan-ignore-line */
            AdvancedNumber::make('Longitude', fn () => $this->location->getLng())->decimals(5)->copyable(), /** @phpstan-ignore-line */
            Boolean::make('Natura 2000', 'natura_2000_area'),
            Stack::make('Links', [
                URL::make('Protected Planet', fn () => 'https://www.protectedplanet.net/'.$this->wdpa_id), /** @phpstan-ignore-line */
                URL::make('WWFF', fn () => 'https://wwff.co/directory/?showRef='.$this->reference), /** @phpstan-ignore-line */
                URL::make('Kansalaisen karttapaikka', fn () => 'https://asiointi.maanmittauslaitos.fi/karttapaikka/?lang=fi&share=customMarker&n='.$this->getETRS89Coordinates()->getNorthing().'&e='.$this->getETRS89Coordinates()->getEasting().'&title='.$this->reference.'&desc='.urlencode($this->name).'&zoom=8'), /** @phpstan-ignore-line */
                URL::make('Paikkatietoikkuna', fn () => 'https://kartta.paikkatietoikkuna.fi/?zoomLevel=10&coord='.$this->getETRS89Coordinates()->getEasting().'_'.$this->getETRS89Coordinates()->getNorthing().'&mapLayers=802+100+default,1629+100+default,1627+70+default,1628+70+default&markers=2|1|ffde00|'.$this->getETRS89Coordinates()->getEasting().'_'.$this->getETRS89Coordinates()->getNorthing().'|'.$this->reference.'%20-%20'.urlencode($this->name).'&noSavedState=true&showIntro=false'), /** @phpstan-ignore-line */
            ]),

            new Panel('Map', [
                GHMap::make('Map')->latitude(optional($this->location)->getLat())->longitude(optional($this->location)->getLng())->hideFromIndex(), /** @phpstan-ignore-line */
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
        return [
            new Lenses\NonApprovedReferences,
        ];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [
            new MarkReferenceSaved,
        ];
    }
}
