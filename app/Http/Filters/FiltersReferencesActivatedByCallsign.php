<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersReferencesActivatedByCallsign implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $query->whereHas('activators', function (Builder $query) use ($value) {
            $query->whereIn('callsign', [$value]);
        });
    }
}
