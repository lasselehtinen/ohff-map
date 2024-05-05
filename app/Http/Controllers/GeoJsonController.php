<?php

namespace App\Http\Controllers;

use App\Models\Reference;
use DateTime;
use GeoJson\Feature\Feature;
use GeoJson\Feature\FeatureCollection;
use GeoJson\Geometry\Point;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class GeoJsonController extends Controller
{
    /**
     * Display a listing of the references in GeoJSON
     *
     * @queryParam filter[reference] Name of the reference. Example: OHFF-0001
     * @queryParam filter[approval_status] Approval status of the reference. Enum: received, declined, approved, saved. Example: received
     * @queryParam filter[activated] Boolean for whether the reference is activated. Enum: true, false. Example: false
     * @queryParam filter[not_activated] Boolean for whether the reference is not activated. Enum: true, false. Example: false
     *
     * @response {"type":"FeatureCollection","features":[{"type":"Feature","geometry":{"type":"Point","coordinates":[24.19893,61.19891]},"properties":{"reference":"OHFF-0665","is_activated":true,"first_activation_date":"1985-01-24","latest_activation_date":"2016-01-12","name":"Isoj\u00e4rvi","icon":"https:\/\/maps.google.com\/intl\/en_us\/mapfiles\/ms\/micons\/red.png","wdpa_id":90633,"karttapaikka_link":"https:\/\/asiointi.maanmittauslaitos.fi\/karttapaikka\/?lang=fi&share=customMarker&n=6788167.7013611&e=349481.49408152&title=OHFF-0665&desc=Isoj%C3%A4rvi&zoom=8","paikkatietoikkuna_link":"https:\/\/kartta.paikkatietoikkuna.fi\/?zoomLevel=10&coord=349481.49408152_6788167.7013611&mapLayers=802+100+default,1629+100+default,1627+70+default,1628+70+default&markers=2|1|ffde00|349481.49408152_6788167.7013611|OHFF-0665%20-%20Isoj%C3%A4rvi&noSavedState=true&showIntro=false","natura_2000_area":true}}]}
     */
    public function index()
    {
        $references = QueryBuilder::for(Reference::class)
            ->allowedFilters([
                'reference',
                'approval_status',
                AllowedFilter::scope('activated'),
                AllowedFilter::scope('not_activated'),
                AllowedFilter::callback('activated_this_year', function (Builder $query, $value) {
                    $query->whereYear('latest_activation_date', date('Y'))->get();
                }),
                //AllowedFilter::custom('not_activated_by', new FiltersReferencesNotActivatedByCallsign),
                //AllowedFilter::custom('activited_this_year', new FiltersReferencesActivatedThisYear),
            ])
            ->get();

        $features = collect([]);

        foreach ($references as $reference) {
            // Get ETRS89 coordinates/point for Karttapaikka and Paikkatietoikkuna
            $etrs89Coordinates = $reference->getETRS89Coordinates(); /* @phpstan-ignore-line */

            // Define properties
            $properties = [
                'reference' => $reference->reference, /** @phpstan-ignore-line */
                'is_activated' => ! empty($reference->first_activation_date),
                'first_activation_date' => $reference->first_activation_date, /** @phpstan-ignore-line */
                'latest_activation_date' => $reference->latest_activation_date, /** @phpstan-ignore-line */
                //'latest_activator' => $reference->activators->sortByDesc('pivot.activation_date')->pluck('callsign')->first(),
                'name' => $reference->name, /** @phpstan-ignore-line */
                'icon' => $this->getIcon($reference),
                'wdpa_id' => $reference->wdpa_id, /** @phpstan-ignore-line */
                'karttapaikka_link' => 'https://asiointi.maanmittauslaitos.fi/karttapaikka/?lang=fi&share=customMarker&n='.$etrs89Coordinates->getNorthing().'&e='.$etrs89Coordinates->getEasting().'&title='.$reference->reference.'&desc='.urlencode($reference->name).'&zoom=8', /** @phpstan-ignore-line */
                'paikkatietoikkuna_link' => 'https://kartta.paikkatietoikkuna.fi/?zoomLevel=10&coord='.$etrs89Coordinates->getEasting().'_'.$etrs89Coordinates->getNorthing().'&mapLayers=802+100+default,1629+100+default,1627+70+default,1628+70+default&markers=2|1|ffde00|'.$etrs89Coordinates->getEasting().'_'.$etrs89Coordinates->getNorthing().'|'.$reference->reference.'%20-%20'.urlencode($reference->name).'&noSavedState=true&showIntro=false', /** @phpstan-ignore-line */
                'natura_2000_area' => (bool) $reference->natura_2000_area, /** @phpstan-ignore-line */
            ];

            $feature = new Feature(new Point([$reference->location->getLongitude(), $reference->location->getLatitude()]), $properties); /** @phpstan-ignore-line */
            $features->push($feature);
        }

        $featureCollection = (new FeatureCollection($features->toArray()))->jsonSerialize();

        return response()->json($featureCollection);
    }

    /**
     * Returns the icon URL for the given reference
     *
     * @param  \Illuminate\Database\Eloquent\Model  $reference
     * @return string
     */
    public function getIcon($reference)
    {
        if (in_array($reference->approval_status, ['received', 'approved'])) { /** @phpstan-ignore-line */
            return 'https://maps.google.com/intl/en_us/mapfiles/ms/micons/purple.png';
        }

        /* @phpstan-ignore-next-line */
        if (is_null($reference->latest_activation_date)) {
            return 'https://maps.google.com/intl/en_us/mapfiles/ms/micons/tree.png';
        }

        // Calculate years from latest activation
        $currentDate = new DateTime();
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

        return sprintf('https://maps.google.com/intl/en_us/mapfiles/ms/micons/%s.png', $iconColor);
    }
}
