<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('upah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawan')->onDelete('cascade');
            $table->integer('minggu_ke');
            $table->integer('total_dikerjakan');
            $table->integer('total_upah');
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('upah');
    }
};