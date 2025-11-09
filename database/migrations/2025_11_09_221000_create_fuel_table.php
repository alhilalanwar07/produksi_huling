
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
        Schema::create('fuel', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('karyawan_id');
            $table->decimal('jumlah_pengisian', 8, 2);
            $table->unsignedInteger('hm');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('karyawan_id')->references('id')->on('karyawans')->onDelete('cascade');

            // Indexes
            $table->index('unit_id');
            $table->index('karyawan_id');
            $table->index('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel');
    }
};