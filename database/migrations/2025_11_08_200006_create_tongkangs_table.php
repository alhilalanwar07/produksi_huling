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
        Schema::create('tongkangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_tongkang');
            $table->integer('kapasitas');
            $table->timestamps();

            $table->index('nama_tongkang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tongkangs');
    }
};