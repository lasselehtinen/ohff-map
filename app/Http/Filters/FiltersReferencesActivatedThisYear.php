<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersReferencesActivatedThisYear implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        return $query->whereYear('latest_activation_date', date('Y'))->get();
    }
}
