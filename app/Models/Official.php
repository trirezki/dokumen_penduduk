<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Official extends Model
{
    use HasFactory;

    protected $fillable = [
        'nip',
        'name',
        'position',
        'rank',
        'signature',
        'user_id',
    ];

    public function scopeByUserId($query, $id)
    {
        return $query->where('user_id', $id);
    }

    public function can_modified()
    {
        return $this->user_id == auth()->id();
    }
}
