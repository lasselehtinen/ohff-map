<?php

namespace App\Http\Controllers;

use App\Http\Filters\FiltersReferencesActivatedByCallsign;
use App\Http\Filters\FiltersReferencesActivatedThisYear;
use App\Http\Filters\FiltersReferencesNotActivatedByCallsign;
use App\Models\Reference;
use DateTime;
use GeoJson\Feature\Feature;
use GeoJson\Feature\FeatureCollection;
use GeoJson\Geometry\Point;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\QueryBuilderRequest;

class GeoJsonController extends Controller
{
    /**
     * Display GeoJSON listing for the references
     *
     * @return Response
     */
    public function index(Request $request)
    {
        // Filters
        QueryBuilderRequest::setArrayValueDelimiter(';');

        $references = QueryBuilder::for(Reference::class)
            ->allowedFilters([
                AllowedFilter::scope('activated'),
                AllowedFilter::scope('not_activated'),
                AllowedFilter::custom('activated_by', new FiltersReferencesActivatedByCallsign),
                AllowedFilter::custom('not_activated_by', new FiltersReferencesNotActivatedByCallsign),
                AllowedFilter::custom('activited_this_year', new FiltersReferencesActivatedThisYear),
                'reference',
                'approval_status',
            ])
            ->where('status', '!=', 'deleted')->with('activators')->get();

        $features = collect([]);

        foreach ($references as $reference) {
            // Get ETRS89 coordinates/point for Karttapaikka and Paikkatietoikkuna
            $point = $reference->getETRS89Coordinates();

            if (! is_null($point)) {
                // Define properties
                $properties = [
                    'reference' => $reference->reference,
                    'latitude' => $reference->location->latitude,
                    'longitude' => $reference->location->longitude,
                    'is_activated' => ! empty($reference->latest_activation_date),
                    'first_activation_date' => $reference->first_activation_date,
                    'latest_activation_date' => $reference->latest_activation_date,
                    'latest_activator' => $reference->activators->sortByDesc('pivot.activation_date')->pluck('callsign')->first(),
                    'name' => $reference->name,
                    'icon' => $this->getIcon($reference),
                    'wdpa_id' => $reference->wdpa_id,
                    'karttapaikka_link' => 'https://asiointi.maanmittauslaitos.fi/karttapaikka/?lang=fi&share=customMarker&n='.$point->getNorthing().'&e='.$point->getEasting().'&title='.$reference->reference.'&desc='.urlencode($reference->name).'&zoom=8',
                    'paikkatietoikkuna_link' => 'https://kartta.paikkatietoikkuna.fi/?zoomLevel=10&coord='.$point->getEasting().'_'.$point->getNorthing().'&mapLayers=802+100+default,1629+100+default,1627+70+default,1628+70+default&markers=2|1|ffde00|'.$point->getEasting().'_'.$point->getNorthing().'|'.$reference->reference.'%20-%20'.urlencode($reference->name).'&noSavedState=true&showIntro=false',
                    'is_natura_2000_area' => (bool) $reference->natura_2000_area,
                ];

                $feature = new Feature(new Point($reference->location->getCoordinates()), $properties);
                $features->push($feature);
            }
        }

        $featureCollection = new FeatureCollection($features->toArray());

        /** @phpstan-ignore-next-line */
        return response($featureCollection, 200, ['Content-Type => application/json']);
    }

    /**
     * Returns the icon URL for the given reference
     *
     * @param  Model  $reference
     * @return string
     */
    public function getIcon($reference)
    {
        if (in_array($reference->approval_status, ['received', 'approved'])) { /** @phpstan-ignore-line */
            return '/images/markers/purple.svg';
        }

        /* @phpstan-ignore-next-line */
        if (is_null($reference->latest_activation_date)) {
            return '/images/markers/tree.svg';
        }

        // Calculate years from latest activation
        $currentDate = new DateTime;
        $latestActivation = new DateTime($reference->latest_activation_date);
        $diff = $currentDate->diff($latestActivation);

        switch ($diff->y) {
            case '0':
                $iconColor = 'blue';
                break;
            case '1':
                $iconColor = 'green';
                break;
            case '2':
                $iconColor = 'yellow';
                break;
            case '3':
                $iconColor = 'orange';
                break;
            case '4':
                $iconColor = 'red';
                break;
            default:
                $iconColor = 'red';
                break;
        }

        return sprintf('/images/markers/%s.svg', $iconColor);
    }
}
