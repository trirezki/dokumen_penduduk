<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IumkLetter extends Model
{
    use HasFactory;

    public static $must_verification = true;
    public static $type = "IUMK";

    protected $fillable = [
        "id",
        "order",
        "prefix",
        "suffix",
        "company_name",
        "company_address",
        "business",
        "capital",
        "file_pendukung",
        "verified_file",
        "resident_id",
        "official_id",
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

    public function official()
    {
        return $this->belongsTo(LetterOfficial::class);
    }
}
