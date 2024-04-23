<?php

namespace App\Http\Controllers;

use App\Models\Reference;
use DateTime;
use GeoJson\Feature\Feature;
use GeoJson\Feature\FeatureCollection;
use GeoJson\Geometry\Point;

class GeoJsonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $references = Reference::all();
        $features = collect([]);

        foreach ($references as $reference) {
            // Define properties
            $properties = [
                'reference' => $reference->reference,
                'is_activated' => ! empty($reference->first_activation_date),
                'first_activation_date' => $reference->first_activation_date,
                'latest_activation_date' => $reference->latest_activation_date,
                //'latest_activator' => $reference->activators->sortByDesc('pivot.activation_date')->pluck('callsign')->first(),
                'name' => $reference->name,
                'icon' => $this->getIcon($reference),
                'wdpa_id' => $reference->wdpa_id,
                //'karttapaikka_link' => 'https://asiointi.maanmittauslaitos.fi/karttapaikka/?lang=fi&share=customMarker&n='.$point->getNorthing().'&e='.$point->getEasting().'&title='.$reference->reference.'&desc='.urlencode($reference->name).'&zoom=8',
                //'paikkatietoikkuna_link' => 'https://kartta.paikkatietoikkuna.fi/?zoomLevel=10&coord='.$point->getEasting().'_'.$point->getNorthing().'&mapLayers=802+100+default,1629+100+default,1627+70+default,1628+70+default&markers=2|1|ffde00|'.$point->getEasting().'_'.$point->getNorthing().'|'.$reference->reference.'%20-%20'.urlencode($reference->name).'&noSavedState=true&showIntro=false',
                'is_natura_2000_area' => (bool) $reference->natura_2000_area,
            ];

            $feature = new Feature(new Point([$reference->location->getLongitude(), $reference->location->getLatitude()]), $properties);
            $features->push($feature);
        }

        return response(new FeatureCollection($features->toArray()), 200, ['Content-Type => application/json']);
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
    }
}
