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
        Schema::create('karyawans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_karyawan', 100);
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->string('tempat_lahir', 50);
            $table->date('tanggal_lahir');

            $table->foreignId('jabatan_id')->nullable()->constrained('jabatans')->nullOnDelete();
            $table->foreignId('devisi_id')->nullable()->constrained('devisis')->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('pendidikan_id')->nullable()->constrained('pendidikans')->nullOnDelete();
            $table->foreignId('agama_id')->nullable()->constrained('agamas')->nullOnDelete();

            $table->date('tanggal_masuk');
            $table->decimal('gaji_pokok', 12, 2);
            $table->enum('status', ['aktif', 'non-aktif'])->default('aktif');

            $table->timestamps();

            // Indexes untuk query performa
            $table->index('nama_karyawan');
            $table->index('status');
            $table->index(['jabatan_id', 'devisi_id', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karyawans');
    }
};