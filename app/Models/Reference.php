<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use PHPCoord\CoordinateReferenceSystem\Geographic2D;
use PHPCoord\CoordinateReferenceSystem\Projected;
use PHPCoord\Exception\UnknownConversionException;
use PHPCoord\Point\GeographicPoint;
use PHPCoord\UnitOfMeasure\Angle\Degree;

class Reference extends Model
{
    use HasFactory, HasSpatial, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['reference', 'status', 'name', 'county', 'iota_reference', 'location', 'suggested', 'wdpa_id', 'latest_activation_date', 'valid_from', 'approval_status'];

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('d.m.Y');
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'location' => Point::class,
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
        return $query->whereNotNull('latest_activation_date');
    }

    /**
     * Scope for the reference is not activated
     */
    public function scopeNotActivated(Builder $query): Builder
    {
        return $query->whereNull('latest_activation_date');
    }

    /**
     * Get the external links shown in the admin panel.
     *
     * @return array<string, string>
     */
    public function adminLinks(): array
    {
        $links = [
            'Protected Planet' => 'https://www.protectedplanet.net/'.$this->wdpa_id,
            'WWFF' => 'https://wwff.co/directory/?showRef='.$this->reference,
        ];

        $point = $this->getETRS89Coordinates();

        if (is_null($point)) {
            return $links;
        }

        return $links + [
            'Kansalaisen karttapaikka' => 'https://asiointi.maanmittauslaitos.fi/karttapaikka/?lang=fi&share=customMarker&n='.$point['northing'].'&e='.$point['easting'].'&title='.$this->reference.'&desc='.urlencode((string) $this->name).'&zoom=8',
            'Paikkatietoikkuna' => 'https://kartta.paikkatietoikkuna.fi/?zoomLevel=10&coord='.$point['easting'].'_'.$point['northing'].'&mapLayers=802+100+default,1629+100+default,1627+70+default,1628+70+default&markers=2|1|ffde00|'.$point['easting'].'_'.$point['northing'].'|'.$this->reference.'%20-%20'.urlencode((string) $this->name).'&noSavedState=true&showIntro=false',
        ];
    }

    /**
     * Get the latest activator's callsign for the given references, keyed by reference ID.
     *
     * @param  Collection<int, int>  $referenceIds
     * @return Collection<int, string>
     */
    public static function latestActivators(Collection $referenceIds): Collection
    {
        // Later rows overwrite earlier ones, so each reference is left with its latest activation
        return DB::table('user_activations')
            ->join('users', 'users.id', '=', 'user_activations.user_id')
            ->whereIn('user_activations.reference_id', $referenceIds)
            ->orderBy('user_activations.activation_date')
            ->orderByDesc('user_activations.id')
            ->pluck('users.callsign', 'user_activations.reference_id');
    }

    /**
     * Return ETRS89 coordinates for the given reference. Only the coordinates are
     * cached, because unserializing the whole point is slow when done for every marker.
     *
     * @return array{northing: string, easting: string}|null
     */
    public function getETRS89Coordinates()
    {
        if (is_null($this->location)) {
            return null;
        }

        return Cache::rememberForever('etrs89-coordinates-'.$this->id, function () {
            // Converting from WGS 84 to ETRS89
            $from = GeographicPoint::create(
                Geographic2D::fromSRID(Geographic2D::EPSG_WGS_84),
                new Degree($this->location->latitude),
                new Degree($this->location->longitude),
                null
            );

            $toCRS = Projected::fromSRID(Projected::EPSG_ETRS89_TM35FIN_N_E);

            try {
                $point = $from->convert($toCRS);

                return ['northing' => (string) $point->getNorthing(), 'easting' => (string) $point->getEasting()];
            } catch (UnknownConversionException $e) {
                return null;
            }
        });
    }
}
