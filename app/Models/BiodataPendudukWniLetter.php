<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FamilyMember;

class BiodataPendudukWniLetter extends Model
{
    use HasFactory;

    public static $must_verification = true;
    public static $type = "BIODATA_PENDUDUK_WNI";

    protected $fillable = [
        "order",
        "prefix",
        "suffix",
        "verified_file",
        "rt",
        "rw",
        "zip_code",
        "phone",
        "province",
        "district",
        "sub_district",
        "village",
        "dusun",
        "rt_name",
        "rw_name",
        'file_pendukung',
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
        return $this->prefix . $this->order . $this->suffix;
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
        return $this->hasOne(FamilyMember::class)->ofMany([
            'head_of_family' => 'max',
        ], function ($query) {
            $query->where('head_of_family', 1);
        });
    }

    public function data_keluarga()
    {
        return $this->hasMany(FamilyMember::class)->where('head_of_family', 0);
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