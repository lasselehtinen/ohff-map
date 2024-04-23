<?php

namespace App\Models;

use DateTimeInterface;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;
use PHPCoord\CoordinateReferenceSystem\Geographic2D;
use PHPCoord\CoordinateReferenceSystem\Projected;
use PHPCoord\Point\GeographicPoint;
use PHPCoord\UnitOfMeasure\Angle\Degree;

class Reference extends Model
{
    use HasFactory, Searchable, SpatialTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['reference', 'status', 'name', 'iota_reference', 'location', 'suggested', 'wdpa_id', 'latest_activation_date', 'valid_from'];

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('d.m.Y');
    }

    /**
     * The attributes that are spatial
     */
    protected $spatialFields = [
        'location',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return $this->only('reference', 'name');
    }

    /**
     * Get the program that owns the reference.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the dxcc that owns the reference.
     */
    public function dxcc(): BelongsTo
    {
        return $this->belongsTo(Dxcc::class);
    }

    /**
     * Get the continent that owns the reference.
     */
    public function continent(): BelongsTo
    {
        return $this->belongsTo(Continent::class);
    }

    /**
     * The users that have activated the reference
     */
    public function activators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_activations')->withPivot('activation_date');
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
                new Degree($this->location->getLat()),
                new Degree($this->location->getLng()),
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
