<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert enum type to a flexible VARCHAR so we can store domain-specific notification types
        // Use raw statement to avoid requiring doctrine/dbal for change()
        DB::statement("ALTER TABLE `notifications` MODIFY `type` VARCHAR(50) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // revert back to original enum - listing the previous allowed values
        DB::statement("ALTER TABLE `notifications` MODIFY `type` ENUM('email','sms','system') NOT NULL");
    }
};
