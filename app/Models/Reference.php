<?php

namespace App\Models;

use Clickbar\Magellan\Database\Eloquent\HasPostgisColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use PHPCoord\CoordinateReferenceSystem\Geographic2D;
use PHPCoord\CoordinateReferenceSystem\Projected;
use PHPCoord\Point\GeographicPoint;
use PHPCoord\UnitOfMeasure\Angle\Degree;

class Reference extends Model
{
    use HasFactory, HasPostgisColumns;

    /**
     * List of PostGIS columns and their definitions
     */
    protected array $postgisColumns = [
        'location' => [
            'type' => 'geometry',
            'srid' => 4326,
        ],
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'first_activation_date' => 'date',
            'latest_activation_date' => 'date',
            'wdpa_id' => 'int',
            'natura_2000_area' => 'boolean',
        ];
    }

    /**
     * The operators that have activated the reference
     */
    public function activators(): BelongsToMany
    {
        return $this->belongsToMany(Operator::class, 'operator_activations')->withPivot('activation_date');
    }

    /**
     * Scope for the reference is activated
     */
    public function scopeActivated(Builder $query): Builder
    {
        return $query->whereNotNull('first_activation_date');
    }

    /**
     * Scope for the reference is not activated
     */
    public function scopeNotActivated(Builder $query): Builder
    {
        return $query->whereNull('first_activation_date');
    }

    /**
     * Return ETRS89 coordinates for the given reference
     *
     * @return \PHPCoord\Point\ProjectedPoint|null
     */
    public function getETRS89Coordinates()
    {
        $point = Cache::rememberForever('etrs98-'.$this->id, function () {
            // Converting from WGS 84 to ETRS89
            $from = GeographicPoint::create(
                Geographic2D::fromSRID(Geographic2D::EPSG_WGS_84),
                new Degree($this->location->getLatitude()),
                new Degree($this->location->getLongitude()),
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
