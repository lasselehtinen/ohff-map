<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProgramResource;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;

class ProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $programs = QueryBuilder::for(Program::class)
            ->allowedFilters(['name'])
            ->paginate()
            ->appends(request()->query());

        return ProgramResource::collection($programs);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return ProgramResource
     */
    public function show($id)
    {
        return new ProgramResource(Program::with('references')->findOrFail($id));
    }
}
