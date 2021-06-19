<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['reference', 'status', 'name', 'latitude', 'longitude', 'iota_reference'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

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
        return $this->belongsToMany(User::class, 'user_activations', 'user_id', 'reference_id')->withPivot('activation_date');
    }    
}
