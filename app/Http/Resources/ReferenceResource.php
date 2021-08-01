<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'name' => $this->name,
            'latitude' => $this->location->getLat(),
            'longitude' => $this->location->getLng(),
            'iota_reference' => $this->iota_reference,
            'program' => new ProgramResource($this->program),
            'dxcc' => new DxccResource($this->dxcc),
            'continent' => new ContinentResource($this->continent),
            'activations' => UserActivationResource::collection($this->activators),
        ];
    }
}
