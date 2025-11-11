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
        Schema::create('retase_tonase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pms_id')->constrained('retase_tonase_pms')->cascadeOnUpdate()->cascadeOnDelete();
            // Per item wajib menyimpan penyewa (site) dan material agar konsisten
            $table->foreignId('site_id')->constrained('sites')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('tonase', 8, 2); // tonase per 1 retase (detail)
            $table->timestamps();

            $table->index(['pms_id', 'site_id', 'material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retase_tonase_items');
    }
};