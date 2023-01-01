<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DamiuLetterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $this["is_verified"] = $this->is_verified();
        $this["can_verified"] = $this->can_verified();
        $this["can_modified"] = $this->can_modified();
        $this["reference_number"] = $this->get_reference_number();
        $this["file_pendukung"] = $this->file_pendukung ? ('storage/' . $this->file_pendukung) : null;
        // $this = 
        return parent::toArray($this);
    }
}
