<?php

namespace App\Nova\Lenses;

use App\Nova\Actions\MarkReferenceSaved;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;
use SimpleSquid\Nova\Fields\AdvancedNumber\AdvancedNumber;

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

            AdvancedNumber::make('Latitude', fn () => $this->location->getLat())->decimals(5)->copyable(), /** @phpstan-ignore-line */
            AdvancedNumber::make('Longitude', fn () => $this->location->getLng())->decimals(5)->copyable(), /** @phpstan-ignore-line */
            Stack::make('Links', [
                URL::make('Protected Planet', fn () => 'https://www.protectedplanet.net/'.$this->wdpa_id), /** @phpstan-ignore-line */
                URL::make('WWFF', fn () => 'https://wwff.co/directory/?showRef='.$this->reference), /** @phpstan-ignore-line */
                URL::make('Kansalaisen karttapaikka', fn () => 'https://asiointi.maanmittauslaitos.fi/karttapaikka/?lang=fi&share=customMarker&n='.$this->getETRS89Coordinates()->getNorthing().'&e='.$this->getETRS89Coordinates()->getEasting().'&title='.$this->reference.'&desc='.urlencode($this->name).'&zoom=8'), /** @phpstan-ignore-line */
                URL::make('Paikkatietoikkuna', fn () => 'https://kartta.paikkatietoikkuna.fi/?zoomLevel=10&coord='.$this->getETRS89Coordinates()->getEasting().'_'.$this->getETRS89Coordinates()->getNorthing().'&mapLayers=802+100+default,1629+100+default,1627+70+default,1628+70+default&markers=2|1|ffde00|'.$this->getETRS89Coordinates()->getEasting().'_'.$this->getETRS89Coordinates()->getNorthing().'|'.$this->reference.'%20-%20'.urlencode($this->name).'&noSavedState=true&showIntro=false'), /** @phpstan-ignore-line */
            ]),
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
