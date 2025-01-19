<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stocks';

    protected $fillable = [
        'stock'
    ];

    public function barang()
    {
        return $this->hasOne(Barang::class, 'stock_id', 'id');
    }
}
