<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OfficialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "nip" => $this->nip,
            "name" => $this->name,
            "position" => $this->position,
            "rank" => $this->rank,
            "signature" => $this->signature,
            "can_modified" => $this->can_modified(),
        ];
    }
}
