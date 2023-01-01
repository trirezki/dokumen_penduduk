<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpwniLetter extends Model
{
    use HasFactory;

    public static $must_verification = true;
    public static $type = "SKPWNI";

    protected $fillable = [
        "prefix",
        "suffix",
        "prefix_desa",
        "suffix_desa",
        "verified_file",
        "family_card_number",
        "rt",
        "rw",
        "village",
        "sub_district",
        "district",
        "province",
        "zip_code",
        "phone",
        "reason_to_move",
        "moving_destination",
        "moving_destination_rt",
        "moving_destination_rw",
        "moving_destination_village",
        "moving_destination_sub_district",
        "moving_destination_district",
        "moving_destination_province",
        "moving_destination_zip_code",
        "moving_destination_phone",
        "move_classification",
        "type_of_move",
        "status_not_move",
        "status_move",
        "moving_date_plan",
        "kepala_keluarga_id",
        "file_pendukung",
        "penandatangan_kecamatan_id",
        "penandatangan_desa_id",
        "user_id",
    ];

    public function is_verified()
    {
        return $this->verified_file != null;
    }

    public function can_verified()
    {
        return (($this->user->parent_id == auth()->id() && self::$must_verification) || (!self::$must_verification && $this->can_modified())) && !$this->is_verified();
    }

    public function can_modified() {
        return $this->user_id == auth()->id();
    }

    public function get_reference_number()
    {
        return $this->prefix . $this->id . $this->suffix;
    }

    public function get_reference_number_desa()
    {
        return $this->prefix_desa . $this->id . $this->suffix_desa;
    }

    public function scopeByUserId($query, $id)
    {
        return $query->where('user_id', $id)
            ->orWhereHas('user', function ($query) use ($id) {
                $query->where('parent_id', $id);
            });
    }

    public function kepala_keluarga()
    {
        return $this->hasOne(Resident::class, 'id', 'kepala_keluarga_id');
    }

    public function data_keluarga()
    {
        return $this->hasMany(FamilySkpwniLetter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function penandatangan_kecamatan()
    {
        return $this->belongsTo(LetterOfficial::class, 'penandatangan_kecamatan_id', 'id');
    }

    public function penandatangan_desa()
    {
        return $this->belongsTo(LetterOfficial::class, 'penandatangan_desa_id', 'id');
    }
}
