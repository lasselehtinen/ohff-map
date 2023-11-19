<?php

namespace App\Http\Controllers;

use App\Http\Filters\FiltersReferencesActivatedByCallsign;
use App\Http\Filters\FiltersReferencesActivatedThisYear;
use App\Http\Filters\FiltersReferencesNotActivatedByCallsign;
use App\Models\Reference;
use DateTime;
use GeoJson\Feature\Feature;
use GeoJson\Feature\FeatureCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
                AllowedFilter::custom('activited_this_year', new FiltersReferencesActivatedThisYear),
                'reference',
                'approval_status',
            ])
            ->where('status', '!=', 'deleted')->with('activators')->get();

        $features = collect([]);

        foreach ($references as $reference) {
            // Get ETRS89 coordinates/point for Karttapaikka and Paikkatietoikkuna
            $point = $this->getETRS89Coordinates($reference);

            // Define properties
            $properties = [
                'reference' => $reference->reference, /** @phpstan-ignore-line */
                'is_activated' => ! empty($reference->first_activation_date),
                'first_activation_date' => $reference->first_activation_date, /** @phpstan-ignore-line */
                'latest_activation_date' => $reference->latest_activation_date, /** @phpstan-ignore-line */
                'latest_activator' => $reference->activators->sortByDesc('pivot.activation_date')->pluck('callsign')->first(), /** @phpstan-ignore-line */
                'name' => $reference->name, /** @phpstan-ignore-line */
                'icon' => $this->getIcon($reference),
                'wdpa_id' => $reference->wdpa_id, /** @phpstan-ignore-line */
                'karttapaikka_link' => 'https://asiointi.maanmittauslaitos.fi/karttapaikka/?lang=fi&share=customMarker&n='.$point->getNorthing().'&e='.$point->getEasting().'&title='.$reference->reference.'&desc='.urlencode($reference->name).'&zoom=8', /** @phpstan-ignore-line */
                'paikkatietoikkuna_link' => 'https://kartta.paikkatietoikkuna.fi/?zoomLevel=10&coord='.$point->getEasting().'_'.$point->getNorthing().'&mapLayers=802+100+default,1629+100+default,1627+70+default,1628+70+default&markers=2|1|ffde00|'.$point->getEasting().'_'.$point->getNorthing().'|'.$reference->reference.'%20-%20'.urlencode($reference->name).'&noSavedState=true&showIntro=false', /** @phpstan-ignore-line */
                'is_natura_2000_area' => (bool) $reference->natura_2000_area, /** @phpstan-ignore-line */
            ];

            $feature = new Feature($reference->location->jsonSerialize(), $properties); /** @phpstan-ignore-line */
            $features->push($feature);
        }

        $featureCollection = new FeatureCollection($features->toArray());

        /** @phpstan-ignore-next-line */
        return response($featureCollection, 200, ['Content-Type => application/json']);
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

    /**
     * Return ETRS89 coordinates for the given reference
     *
     * @param  \Illuminate\Database\Eloquent\Model  $reference
     * @return \PHPCoord\Point\ProjectedPoint|null
     */
    public function getETRS89Coordinates($reference)
    {
        /** @phpstan-ignore-next-line */
        $point = Cache::rememberForever('etrs98-'.$reference->name, function () use ($reference) {
            // Converting from WGS 84 to ETRS89
            $from = GeographicPoint::create(
                Geographic2D::fromSRID(Geographic2D::EPSG_WGS_84),
                new Degree($reference->location->getLat()), /** @phpstan-ignore-line */
                new Degree($reference->location->getLng()), /** @phpstan-ignore-line */
                null
            );

            $toCRS = Projected::fromSRID(Projected::EPSG_ETRS89_TM35FIN_N_E);

            try {
                $point = $from->convert($toCRS); // $to instanceof ProjectedPoint
            } catch (\PHPCoord\Exception\UnknownConversionException $e) {
                return null;
            }

            return $point;
        });

        return $point;

    }
}
