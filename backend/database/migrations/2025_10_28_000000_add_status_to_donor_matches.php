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
        Schema::table('donor_matches', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Accepted', 'Declined'])->default('Pending')->after('match_score');
            $table->timestamp('scheduled_at')->nullable()->after('status');
            $table->string('scheduled_location')->nullable()->after('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donor_matches', function (Blueprint $table) {
            $table->dropColumn(['status', 'scheduled_at', 'scheduled_location']);
        });
    }
};
