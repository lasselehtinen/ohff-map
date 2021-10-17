<?php

namespace App\Http\Controllers;

use App\Http\Filters\FiltersReferencesActivatedByCallsign;
use App\Http\Filters\FiltersReferencesNotActivatedByCallsign;
use App\Models\Reference;
use App\Models\User;
use DateTime;
use GeoJson\Feature\Feature;
use GeoJson\Feature\FeatureCollection;
use GeoJson\Geometry\Point;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class GeoJsonController extends Controller
{
    /**
     * Display GeoJSON listing for the references
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $references = QueryBuilder::for(Reference::class)
            ->allowedFilters([
                AllowedFilter::scope('activated'),
                AllowedFilter::scope('not_activated'),
                AllowedFilter::custom('activated_by', new FiltersReferencesActivatedByCallsign),
                AllowedFilter::custom('not_activated_by', new FiltersReferencesNotActivatedByCallsign), 
            ])
            ->where('status', '!=', 'deleted')->get();

        $features = [];

        foreach ($references as $reference) {
            $point = new Point([
                $reference->location->getLng(),
                $reference->location->getLat(),
            ]);

            // Determine icon based on the latest activation
            $latestActivator = $reference->activators->sortBy('user_activations.activation_date')->pluck('callsign')->first();
            
            if (is_null($reference->first_activation_date)) {
                $icon = 'http://maps.google.com/intl/en_us/mapfiles/ms/micons/tree.png';
            } else {
                // Calculate years from latest activation
                $currentDate = new DateTime();
                $latestActivation = new DateTime($reference->latest_activation_date);
                $diff = $currentDate->diff($latestActivation);

                switch ($diff->y) {
                    case '0':
                        $icon = 'http://maps.google.com/intl/en_us/mapfiles/ms/micons/blue.png';
                        break;   
                    case '1':
                        $icon = 'http://maps.google.com/intl/en_us/mapfiles/ms/micons/green.png';
                        break;
                    case '2':
                        $icon = 'http://maps.google.com/intl/en_us/mapfiles/ms/micons/yellow.png';
                        break;
                    case '3':
                        $icon = 'http://maps.google.com/intl/en_us/mapfiles/ms/micons/orange.png';
                        break;
                    case '4':
                        $icon = 'http://maps.google.com/intl/en_us/mapfiles/ms/micons/red.png';
                        break;                                                                     
                    default:
                        $icon = 'http://maps.google.com/intl/en_us/mapfiles/ms/micons/red.png';
                        break;
                }
            }

            $feature = new Feature($point, [
                'reference' => $reference->reference,
                'is_activated' => !empty($reference->first_activation_date),
                'first_activation_date' => $reference->first_activation_date,
                'latest_activation_date' => $reference->latest_activation_date,
                'latest_activator' => $latestActivator,
                'name' => $reference->name,
                'icon' => $icon,
            ]);

            array_push($features, $feature);
        }

        $featureCollection = new FeatureCollection($features);

        return response($featureCollection, 200, ['Content-Type => application/json']);
    }
}
