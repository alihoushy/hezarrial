<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bank messages carry balances and card digits. The text is now stored encrypted
 * (model cast) and may be cleared once the message has been dealt with.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incoming_sms', function (Blueprint $table) {
            $table->text('raw_message')->nullable()->change();
        });

        DB::table('incoming_sms')->whereNotNull('raw_message')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('incoming_sms')->where('id', $row->id)->update(['raw_message' => Crypt::encryptString($row->raw_message)]);
            }
        });
    }

    public function down(): void
    {
        DB::table('incoming_sms')->whereNotNull('raw_message')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('incoming_sms')->where('id', $row->id)->update(['raw_message' => Crypt::decryptString($row->raw_message)]);
            }
        });

        DB::table('incoming_sms')->whereNull('raw_message')->update(['raw_message' => '']);

        Schema::table('incoming_sms', function (Blueprint $table) {
            $table->text('raw_message')->nullable(false)->change();
        });
    }
};
