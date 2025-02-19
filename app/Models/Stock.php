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

    public function reduceStock($amount)
    {
        if ($this->stock <= 0) {
            throw new \Exception('Stock habis');
        }

        if ($this->stock - $amount < 0) {
            throw new \Exception('Tidak bisa mengurangi stok di bawah 0');
        }

        $this->stock -= $amount;
        $this->save();
    }

}
