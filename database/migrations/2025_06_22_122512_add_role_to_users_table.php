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
        Schema::table('users', function (Blueprint $table) {
            // Menambah kolom 'role' sebagai string
            // Defaultnya 'tendik' (staff non-pengajar)
            // AFTER 'remember_token'
            $table->enum('role', ['admin', 'tendik', 'guru_piket'])->default('tendik')->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Menghapus kolom 'role' jika migrasi di-rollback
            $table->dropColumn('role');
        });
    }
};
