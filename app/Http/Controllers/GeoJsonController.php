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
use GeoJson\Geometry\Polygon;
use Grimzy\LaravelMysqlSpatial\Types\LineString as SpatialLineString;
use Grimzy\LaravelMysqlSpatial\Types\Point as SpatialPoint;
use Grimzy\LaravelMysqlSpatial\Types\Polygon as SpatialPolygon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\QueryBuilderRequest;

class GeoJsonController extends Controller
{
    /**
     * Display GeoJSON listing for the references
     *
     * @return \Illuminate\Http\Response
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
                AllowedFilter::callback('within', function (Builder $query, $boundaries) {
                    // Create polygon from SW and NE coordinates
                    $boundPolygon = $this->getBoundPolygon($boundaries[0], $boundaries[1]);
                    $query->within('location', $boundPolygon);
                }),
            ])
            ->where('status', '!=', 'deleted')->with('activators')->get();

        $features = [];

        foreach ($references as $reference) {
            // Get the latest activator
            $latestActivator = $reference->activators->sortBy('user_activations.activation_date')->pluck('callsign')->first();

            // Get icon based on when the reference was last activated
            $icon = $this->getIcon($reference);

            $feature = new Feature($reference->location->jsonSerialize(), [
                'reference' => $reference->reference,
                'is_activated' => !empty($reference->first_activation_date),
                'first_activation_date' => $reference->first_activation_date,
                'latest_activation_date' => $reference->latest_activation_date,
                'latest_activator' => $latestActivator,
                'name' => $reference->name,
                'icon' => $icon,
            ]);

            array_push($features, $feature);

            // Add geometry as a feature if zoom level is high enough
            if ($request->input('zoom', 5) > 7) {
                if (is_null($reference->area) === false) {
                    $feature = new Feature($reference->area->jsonSerialize());
                    array_push($features, $feature);
                }
            }
        }

        $featureCollection = new FeatureCollection($features);

        return response($featureCollection, 200, ['Content-Type => application/json']);
    }

    /**
     * Returns the icon URL for the given reference
     * @param  \Illuminate\Database\Eloquent\Model $reference
     * @return string
     */
    public function getIcon($reference)
    {
        if (is_null($reference->first_activation_date)) {
            return 'https://maps.google.com/intl/en_us/mapfiles/ms/micons/tree.png';
        }

        // Calculate years from latest activation
        $currentDate = new DateTime();
        $latestActivation = new DateTime($reference->latest_activation_date);
        $diff = $currentDate->diff($latestActivation);

        switch ($diff->y) {
            case '0':
                $icon = 'https://maps.google.com/intl/en_us/mapfiles/ms/micons/blue.png';
                break;
            case '1':
                $icon = 'https://maps.google.com/intl/en_us/mapfiles/ms/micons/green.png';
                break;
            case '2':
                $icon = 'https://maps.google.com/intl/en_us/mapfiles/ms/micons/yellow.png';
                break;
            case '3':
                $icon = 'https://maps.google.com/intl/en_us/mapfiles/ms/micons/orange.png';
                break;
            case '4':
                $icon = 'https://maps.google.com/intl/en_us/mapfiles/ms/micons/red.png';
                break;
            default:
                $icon = 'https://maps.google.com/intl/en_us/mapfiles/ms/micons/red.png';
                break;
        }

        return $icon;
    }

    /**
     * Get the rectable polygon for the bound
     * @param  string $southWestBounds
     * @param  string $northEastBounds
     * @return \Grimzy\LaravelMysqlSpatial\Types\Polygon
     */
    public function getBoundPolygon($southWestBounds, $northEastBounds)
    {
        $regExp = '/\((\d+.\d+), (\d+.\d+)\)/';

        $southLimit = preg_replace($regExp, '$1', $southWestBounds);
        $westLimit = preg_replace($regExp, '$2', $southWestBounds);
        $northLimit = preg_replace($regExp, '$1', $northEastBounds);
        $eastLimit = preg_replace($regExp, '$2', $northEastBounds);
        
        // We around startig from SW and going around clockwise and connecting to start
        $polygon = new SpatialPolygon([new SpatialLineString([
            new SpatialPoint($southLimit, $westLimit),
            new SpatialPoint($northLimit, $westLimit),
            new SpatialPoint($northLimit, $eastLimit),
            new SpatialPoint($southLimit, $eastLimit),
            new SpatialPoint($southLimit, $westLimit),
        ])]);

        return $polygon;
    }
}
