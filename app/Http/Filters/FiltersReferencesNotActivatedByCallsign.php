<?php

namespace App\Http\Filters;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersReferencesNotActivatedByCallsign implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $user = User::where('callsign', $value)->first();

        if (! is_null($user)) {
            $query->whereNotIn('id', $user->activations->pluck('id')->unique());
        }
    }
}
