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
        Schema::create('hauling_pendek_perusdas', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('karyawan_id')->constrained('karyawans')->cascadeOnDelete();
            // Mengacu ke tabel sites sebagai mitra
            $table->foreignId('mitra_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->unsignedInteger('jumlah_retase');
            $table->timestamps();

            // Index tambahan untuk query yang sering dipakai
            $table->index(['unit_id']);
            $table->index(['karyawan_id']);
            $table->index(['mitra_id']);
            $table->index(['material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hauling_pendek_perusdas');
    }
};