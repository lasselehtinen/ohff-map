<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReferenceResource;
use App\Models\Continent;
use App\Models\Dxcc;
use App\Models\Program;
use App\Models\Reference;
use Axiom\Rules\LocationCoordinates;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ReferenceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
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
                }

                $query->whereIn('id', $request->user()->activations->pluck('id')->unique());
            }),
            'name',
            'allowed_status',
        ])
        ->paginate()
        ->appends(request()->query());

        return ReferenceResource::collection($references);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \App\Http\Resources\ReferenceResource
     */
    public function show($id)
    {
        return new ReferenceResource(Reference::findOrFail($id));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('suggest-a-reference');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:references',
            'coordinates' => ['required', new LocationCoordinates],
            'protected_planet_link' => 'required|url|regex:/https?:\/\/www.protectedplanet.net\/\d+/',
        ]);

        // Check that WDPA id is not already used
        $wdpaId = intval(basename($request->protected_planet_link));

        $validator->after(function ($validator) use ($wdpaId) {
            if (Reference::where('wdpa_id', $wdpaId)->exists()) { /** @phpstan-ignore-line */
                $validator->errors()->add(
                    'protected_planet_link',
                    'Area with the same Protected Planet / WDPA ID already exists'
                );
            }
        });

        if ($validator->fails()) {
            return redirect('suggest')->withErrors($validator)->withInput();
        }

        $latestReference = Reference::orderByDesc('reference')->first(); /* @phpstan-ignore-line */

        $reference = new Reference;
        $reference->reference = strval(++$latestReference->reference);
        $reference->name = $request->name;
        $reference->status = 'proposed';
        [$latitude, $longitude] = explode(',', $request->coordinates);
        $reference->location = new Point($latitude, $longitude);
        $reference->wdpa_id = $wdpaId;

        // Add relations
        $program = Program::where('name', 'OHFF')->firstOrFail();
        $reference->program()->associate($program);

        $dxcc = Dxcc::where('name', 'OH')->first();
        $reference->dxcc()->associate($dxcc);

        $continent = Continent::where('name', 'EU')->first();
        $reference->continent()->associate($continent);

        $reference->save();

        return redirect('suggest')->with('status', 'Suggested reference '.$reference->reference.' saved. It will be checked and approved soon as possible.');
    }
}
