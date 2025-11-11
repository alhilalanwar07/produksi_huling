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
        Schema::create('retase_tonase_pms', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('karyawans')->cascadeOnUpdate()->cascadeOnDelete();
            // site/material dipindahkan ke tabel detail (items)
            // Jumlah retase per satu input tonase di detail
            $table->unsignedInteger('jumlah_retase_per_tonase');
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'driver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retase_tonase_pms');
    }
};