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
        Schema::create('bargings', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('karyawan_id')->constrained('karyawans')->onDelete('cascade');
            $table->foreignId('jenis_barging_id')->constrained('jenis_bargings')->onDelete('cascade');
            $table->string('tongkang_id');
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            // Nilai retase per entri barging. Gunakan decimal agar fleksibel.
            $table->decimal('retase', 10, 2)->default(0);
            $table->timestamps();

            $table->index('tanggal');
            $table->index('unit_id');
            $table->index('karyawan_id');
            $table->index('jenis_barging_id');
            $table->index('site_id');
            $table->index('tongkang_id');
            $table->index('retase');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bargings');
    }
};