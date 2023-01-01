<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilySkpwniLetter extends Model
{
    protected $fillable = [
        "shdk",
        "resident_id",
        "skpwni_letter_id",
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }
}
