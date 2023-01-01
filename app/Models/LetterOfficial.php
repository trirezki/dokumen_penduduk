<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterOfficial extends Model
{
    use HasFactory;

    protected $fillable = [
        "nip",
        "name",
        "position",
        "rank",
        "signature",
        "user_id"
    ];
}
