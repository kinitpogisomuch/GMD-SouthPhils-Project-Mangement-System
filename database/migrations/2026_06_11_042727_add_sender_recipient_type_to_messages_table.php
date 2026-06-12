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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('sender_type')->nullable()->after('sender_id');
            $table->string('recipient_type')->nullable()->after('recipient_id');
            $table->index(['sender_type', 'sender_id', 'recipient_type', 'recipient_id'], 'messages_sender_recipient_idx');
            $table->index(['recipient_type', 'recipient_id', 'is_read'], 'messages_recipient_unread_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_sender_recipient_idx');
            $table->dropIndex('messages_recipient_unread_idx');
            $table->dropColumn(['sender_type', 'recipient_type']);
        });
    }
};
