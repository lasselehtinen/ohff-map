<?php

namespace App\Http\Filters;

use App\Models\User;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FiltersReferencesNotActivatedByCallsign implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $user = User::where('callsign', $value)->first();
        $query->whereNotIn('id', $user->activations->pluck('id')->unique());
    }
}
