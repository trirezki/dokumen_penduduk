<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Resident;

class FamilyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        "mother_nik",
        "mother_name",
        "father_nik",
        "father_name",
        "blood_type",
        "family_status",
        "last_study",
        "disabilities",
        "paspor_number",
        "paspor_due_date",
        "birth_certificate_number",
        "marriage_certificate_number",
        "marriage_date",
        "divorce_certificate_number",
        "divorce_date",
        "head_of_family",
        "resident_id",
        "biodata_penduduk_wni_letter_id",
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }
}
