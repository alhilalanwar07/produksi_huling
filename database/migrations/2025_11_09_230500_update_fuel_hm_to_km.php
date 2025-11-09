<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fuel', function (Blueprint $table) {
            // Tambahkan kolom km baru dengan default 0 untuk kompatibilitas data lama
            $table->unsignedInteger('km')->default(0)->after('jumlah_pengisian');
        });

        // Salin data dari hm ke km
        DB::statement('UPDATE fuel SET km = hm');

        Schema::table('fuel', function (Blueprint $table) {
            // Hapus kolom hm setelah migrasi data
            $table->dropColumn('hm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel', function (Blueprint $table) {
            // Kembalikan kolom hm
            $table->unsignedInteger('hm')->default(0)->after('jumlah_pengisian');
        });

        // Salin kembali data km ke hm
        DB::statement('UPDATE fuel SET hm = km');

        Schema::table('fuel', function (Blueprint $table) {
            // Hapus kolom km
            $table->dropColumn('km');
        });
    }
};