<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BiodataPendudukWniLetterResource extends JsonResource
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
        $resident = collect($this->kepala_keluarga['resident']);
        $kepala_keluarga = $this->kepala_keluarga;
        unset($kepala_keluarga['resident']);
        foreach ($resident as $key => $value) {
            $kepala_keluarga[$key] = $value;
        }
        $this["kepala_keluarga"] = $kepala_keluarga;
        $data_keluarga = $this->data_keluarga;
        foreach ($data_keluarga as $key => $value) {
            $resident = collect($data_keluarga[$key]["resident"]);
            unset($data_keluarga[$key]['resident']);
            unset($data_keluarga[$key]['id']);
            foreach ($resident as $key2 => $value2) {
                $data_keluarga[$key][$key2] = $value2;
            }
        }
        $this["data_keluarga"] = $data_keluarga;
        $this["file_pendukung"] = $this->file_pendukung ? ('storage/' . $this->file_pendukung) : null;
        return parent::toArray($this);
    }
}
