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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_lambung', 100)->unique();
            $table->foreignId('karyawan_id')->constrained('karyawans')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('type_unit_id')->constrained('type_units')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('jenis_unit_id')->constrained('jenis_units')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('nomor_polisi', 50)->unique();
            $table->string('nomor_rangka', 100)->unique();
            $table->string('nomor_mesin', 100)->unique();
            $table->timestamps();

            // Index untuk performa pencarian/filter
            $table->index(['karyawan_id', 'type_unit_id', 'jenis_unit_id', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};