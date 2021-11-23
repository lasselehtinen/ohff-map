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
use PHPCoord\CoordinateReferenceSystem\Geographic2D;
use PHPCoord\CoordinateReferenceSystem\Projected;
use PHPCoord\GeographicPoint;
use PHPCoord\UnitOfMeasure\Angle\Degree;
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
                'reference',
            ])

            ->where('status', '!=', 'deleted')->with('activators')->get();

        $features = collect([]);

        foreach ($references as $reference) {
            // Define properties
            $properties = [
                'reference' => $reference->reference,
                'is_activated' => !empty($reference->first_activation_date),
                'first_activation_date' => $reference->first_activation_date,
                'latest_activation_date' => $reference->latest_activation_date,
                'latest_activator' => $reference->activators->sortBy('user_activations.activation_date')->pluck('callsign')->first(),
                'name' => $reference->name,
                'icon' => $this->getIcon($reference),
                'wdpa_id' => $reference->wdpa_id,
                'karttapaikka_link' => $this->getKansalaisenKarttaPaikkaLink($reference),
            ];

            $feature = new Feature($reference->location->jsonSerialize(), $properties);
            $features->push($feature);

            // Add geometry as a feature if zoom level is high enough
            if ($request->input('zoom', 5) > 7 && !is_null($reference->area)) {
                $feature = new Feature($reference->area->jsonSerialize(), $properties);
                $features->push($feature);
            }
        }

        $featureCollection = new FeatureCollection($features->toArray());

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

    public function getKansalaisenKarttaPaikkaLink($reference)
    {
        // Converting from WGS 84 to ETRS89
        $from = GeographicPoint::create(
            Geographic2D::fromSRID(Geographic2D::EPSG_WGS_84),
            new Degree($reference->location->getLat()),
            new Degree($reference->location->getLng()),
            null
        );
        $toCRS = Projected::fromSRID(Projected::EPSG_ETRS89_TM35FIN_N_E);
        
        try {
            $to = $from->convert($toCRS); // $to instanceof ProjectedPoint
        } catch (\PHPCoord\Exception\UnknownConversionException $e) {
            return null;
        }

        return 'https://asiointi.maanmittauslaitos.fi/karttapaikka/?lang=fi&share=customMarker&n=' . $to->getNorthing() . '&e=' . $to->getEasting() .'&title=' . $reference->reference . '&desc=' . urlencode($reference->name) . '&zoom=8';
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
