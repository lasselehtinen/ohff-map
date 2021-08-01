<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReferenceResource;
use App\Models\Reference;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Filters\FiltersNearest;

class ReferenceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $references = QueryBuilder::for(Reference::class)
        ->allowedFilters([
            AllowedFilter::callback('nearest', function ($query, $coordinates) {
                    $point = new Point($coordinates[0], $coordinates[1]);
                    $query->orderByDistance('location', $point);
                }),
            AllowedFilter::callback('activated_by_me', function ($query, $value) use ($request) {
                    if ($value === false) {
                        $query->whereNotIn('id', $request->user()->activations->pluck('id')->unique());
                    } else {
                        $query->whereIn('id', $request->user()->activations->pluck('id')->unique());
                    }
                }),
            'name'
        ])
        ->paginate()
        ->appends(request()->query());

        return ReferenceResource::collection($references);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return new ReferenceResource(Reference::findOrFail($id));
    }
}
