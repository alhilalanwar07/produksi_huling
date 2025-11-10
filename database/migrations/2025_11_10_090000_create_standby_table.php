<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('standby', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->comment('Tanggal kejadian standby');
            $table->string('unit_id', 100)->comment('Nomor lambung unit, referensi ke units.nomor_lambung');
            $table->text('alasan')->comment('Alasan terjadi standby');
            $table->timestamps();

            $table->foreign('unit_id')
                ->references('nomor_lambung')
                ->on('units')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index('tanggal');
            $table->index('unit_id');
        });
    }

    /**
     * Drop tabel 'standby'.
     */
    public function down(): void
    {
        Schema::dropIfExists('standby');
    }
};