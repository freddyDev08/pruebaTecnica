<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->unique();
            $table->string('name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 100)->nullable();
            $table->string('dob', 50)->nullable();
            $table->text('avatar')->nullable();
            $table->string('gender', 60)->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('locale', 60)->nullable();
            $table->json('address')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->string('source_created_at', 100)->nullable();
            $table->json('raw_data');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fakeapi_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('resource', 40)->default('users');
            $table->string('status', 20);
            $table->unsignedInteger('requests')->default(0);
            $table->unsignedInteger('received')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->text('error')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fakeapi_sync_runs');
        Schema::dropIfExists('clientes');
    }
};
