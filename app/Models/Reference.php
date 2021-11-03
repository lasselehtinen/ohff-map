<?php

namespace App\Models;

use App\Models\User;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Searchable;

class Reference extends Model
{
    use HasFactory, SpatialTrait, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['reference', 'status', 'name', 'iota_reference'];

    /**
     * The attributes that are spatial
     */
    protected $spatialFields = [
        'location',
        'area',
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
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the dxcc that owns the reference.
     */
    public function dxcc()
    {
        return $this->belongsTo(Dxcc::class);
    }

    /**
     * Get the continent that owns the reference.
     */
    public function continent()
    {
        return $this->belongsTo(Continent::class);
    }

    /**
     * The users that have activated the reference
     */
    public function activators()
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
}
