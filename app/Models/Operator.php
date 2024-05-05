<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Operator extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['callsign'];

    /**
     * Get the activated references for the operator.
     */
    public function activations(): BelongsToMany
    {
        return $this->belongsToMany(Reference::class, 'operator_activations', 'operator_id', 'reference_id')->withPivot('activation_date', 'qso_count', 'chaser_count');
    }
}
