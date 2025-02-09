<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffProduksi extends Model
{
    use HasFactory;

    protected $table = 'staff_produksi';
    protected $fillable = [
        'users_id',
        'nama',
        'tanggal_lahir',
        'pekerjaan',
        'alamat',
        'telepon',
        'email',

    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id', 'id');
    }
    
    public function upah()
    {
        return $this->hasMany(Upah::class, 'staff_produksi_id');
    }

    public function getTanggalLahirAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function setTanggalLahirAttribute($value)
    {
        $this->attributes['tanggal_lahir'] = Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
    }
}
