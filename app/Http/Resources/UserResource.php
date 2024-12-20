<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User **/
class UserResource extends JsonResource
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
            'callsign' => $this->callsign,
            'name' => $this->name,
            'email' => $this->email,
            'activations' => ReferenceResource::collection($this->activations),
        ];
    }
}
