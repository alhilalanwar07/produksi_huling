<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_sheets', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');

            $table->foreignId('unit_id')->constrained('units')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('karyawan_id')->constrained('karyawans')->cascadeOnUpdate()->restrictOnDelete();

            $table->decimal('shift1_hm_awal', 10, 2)->default(0);
            $table->decimal('shift1_hm_akhir', 10, 2)->default(0);
            $table->decimal('shift2_hm_awal', 10, 2)->nullable();
            $table->decimal('shift2_hm_akhir', 10, 2)->nullable();

            $table->decimal('total_hm', 10, 2)->default(0);
            $table->decimal('total_lembur', 10, 2)->default(0);

            $table->text('keterangan')->nullable();

            $table->foreignId('lokasi_id')->nullable()->constrained('lokasis')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete()->cascadeOnUpdate();

            $table->timestamps();

            // Indexes untuk performa query
            $table->index('tanggal');
            $table->index(['unit_id', 'karyawan_id']);
            $table->index(['lokasi_id', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_sheets');
    }
};