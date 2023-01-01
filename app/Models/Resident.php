<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resident extends Model
{
    use HasFactory;

    protected $fillable = [
        "nik",
        "name",
        "gender",
        "place_of_birth",
        "date_of_birth",
        "profession",
        "marital_status",
        "address",
        "user_id",
    ];

    public function scopeByUserId($query, $id)
    {
        return $query->where('user_id', $id)
            ->orWhereHas('user', function ($query) use ($id) {
                $query->where('parent_id', $id);
            });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
