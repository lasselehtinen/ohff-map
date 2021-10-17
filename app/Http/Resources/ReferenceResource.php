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
            'first_activation_date' => $this->first_activation_date,
            'latest_activation_date' => $this->latest_activation_date,
            'name' => $this->name,
            'latitude' => optional($this->location)->getLat(),
            'longitude' => optional($this->location)->getLng(),
            'iota_reference' => $this->iota_reference,
            'program' => new ProgramResource($this->program),
            'dxcc' => new DxccResource($this->dxcc),
            'continent' => new ContinentResource($this->continent),
            'activators' => UserActivationResource::collection($this->activators),
        ];
    }
}
