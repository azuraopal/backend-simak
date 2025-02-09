<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barang_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_produksi_id')
                ->constrained('staff_produksi', 'id')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('barang_id')
                ->constrained('barang', 'id')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->date('tanggal');
            $table->integer('jumlah_dikerjakan');
            $table->timestamps();
            $table->softDeletes();
            $table->index('tanggal');
            $table->index(['staff_produksi_id', 'tanggal']);
            $table->index(['barang_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_harian');
    }
};
