<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_guru', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('nama_guru'); // Sesuaikan posisi
        });
    }

    public function down(): void
    {
        Schema::table('tbl_guru', function (Blueprint $table) {
            $table->dropColumn('telegram_chat_id');
        });
    }
};
