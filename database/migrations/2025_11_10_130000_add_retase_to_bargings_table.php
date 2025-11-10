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
        Schema::table('bargings', function (Blueprint $table) {
            if (!Schema::hasColumn('bargings', 'retase')) {
                $table->decimal('retase', 10, 2)->default(0)->after('site_id');
                $table->index('retase');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bargings', function (Blueprint $table) {
            if (Schema::hasColumn('bargings', 'retase')) {
                $table->dropIndex(['retase']);
                $table->dropColumn('retase');
            }
        });
    }
};