<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 25)->nullable()->after('name');
            $table->string('account_type', 20)->default('staff')->after('role_id');
            $table->string('approval_status', 20)->default('approved')->after('account_type');
            $table->boolean('is_super_root')->default(false)->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('is_super_root');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
            $table->index(['account_type', 'approval_status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['account_type', 'approval_status']);
            $table->dropColumn(['phone', 'account_type', 'approval_status', 'is_super_root', 'approved_at', 'approved_by']);
        });
    }
};
