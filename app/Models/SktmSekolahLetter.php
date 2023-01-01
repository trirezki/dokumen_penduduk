<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SktmSekolahLetter extends Model
{
    use HasFactory;

    public static $must_verification = true;
    public static $type = "SKTM_SEKOLAH";

    protected $fillable = [
        "id",
        "order",
        "order_desa",
        "prefix",
        "suffix",
        "prefix_desa",
        "suffix_desa",
        "dasar_1",
        "dasar_2",
        "surat_pengantar",
        "kartu_keluarga",
        "verified_file",
        "resident_id",
        "father",
        "mother",
        "used_as",
        "institution",
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

    public function get_reference_number_desa()
    {
        return $this->prefix_desa . $this->order_desa . $this->suffix_desa;
    }

    public function get_capital_parse()
    {
        return "Rp. " . number_format($this->capital,0,",",".") . ",-";
    }

    public function scopeByUserId($query, $id)
    {
        return $query->where('user_id', $id)
            ->orWhereHas('user', function ($query) use ($id) {
                $query->where('parent_id', $id);
            });
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
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
