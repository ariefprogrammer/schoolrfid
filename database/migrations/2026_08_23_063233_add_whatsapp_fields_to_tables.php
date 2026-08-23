<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_pengaturan', function (Blueprint $table) {
            $table->string('fonnte_token')->nullable()->after('telegram_kepsek');
            $table->string('wa_kepsek')->nullable()->after('fonnte_token');
        });

        Schema::table('tbl_guru', function (Blueprint $table) {
            $table->string('no_wa')->nullable()->after('telegram_chat_id');
        });

        Schema::table('tbl_tendik', function (Blueprint $table) {
            $table->string('no_wa')->nullable()->after('rfid');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_pengaturan', function (Blueprint $table) {
            $table->dropColumn(['fonnte_token', 'wa_kepsek']);
        });
        Schema::table('tbl_guru', function (Blueprint $table) {
            $table->dropColumn('no_wa');
        });
        Schema::table('tbl_tendik', function (Blueprint $table) {
            $table->dropColumn('no_wa');
        });
    }
};