<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected function logo(): Attribute
    {
        return new Attribute(
            get: function ($value) {
                if ($value != null) {
                    $value = url('storage/' . $value);
                }
                return $value;
            },
        );
    }

    public function scopeVillage($query)
    {
        return $query->where('type', 'desa');
    }

    public function head_of_institution() {
        return $this->belongsTo(Official::class, 'head_of_institution_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id', 'id');
    }

    public function kop_village()
    {
        return $this->type_village . " " . $this->village;
    }

    public function leader()
    {
        return $this->type_village == "Kelurahan" ? "Lurah" : "Kepala Desa";
    }
}
