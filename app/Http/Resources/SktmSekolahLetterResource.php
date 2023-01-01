<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SktmSekolahLetterResource extends JsonResource
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
        $this["reference_number_desa"] = $this->get_reference_number_desa();
        $this["surat_pengantar"] = $this->surat_pengantar ? ('storage/' . $this->surat_pengantar) : null;
        $this["kartu_keluarga"] = $this->kartu_keluarga ? ('storage/' . $this->kartu_keluarga) : null;
        $this["file_arsip"] = $this->file_arsip ? ('storage/' . $this->file_arsip) : null;
        // $this = 
        return parent::toArray($this);
    }
}
