<?php

namespace App\Models;

use App\Enums\UserRole;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'nama_lengkap',
        'email',
        'password',
        'role',
        'foto_profile'
    ];

    public function getFotoProfileUrlAttribute()
    {
        if ($this->foto_profile) {
            return asset("storage/profile_photos/{$this->foto_profile}");
        }

        return asset('storage/profile_photos/default.jpg');
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class
    ];

    public function karyawan()
    {
        return $this->hasOne(Karyawan::class, 'karyawan_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isKaryawan(): bool
    {
        return $this->role === UserRole::Karyawan;
    }
}