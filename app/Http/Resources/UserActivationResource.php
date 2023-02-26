<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User **/
class UserActivationResource extends JsonResource
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
            'activation_date' => $this->pivot->activation_date, /** @phpstan-ignore-line */
        ];
    }
}
