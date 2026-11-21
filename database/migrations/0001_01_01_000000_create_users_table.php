<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('role', 20)->default('user')->index(); // user, parent, admin, super_admin
            $table->string('account_type', 20)->nullable();        // eleve, personnel
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->string('password')->nullable();
            $table->string('class_name', 30)->nullable();
            $table->string('matricule', 30)->nullable();
            $table->string('position')->nullable();
            $table->string('avatar_path')->nullable();
            $table->integer('balance')->default(0);
            $table->json('allergies')->nullable();
            $table->json('diets')->nullable();
            $table->string('locale', 5)->default('fr');
            $table->string('class_confirmed_for', 20)->nullable();
            $table->integer('low_balance_threshold')->default(1000);
            $table->integer('daily_cap')->nullable();
            $table->boolean('auto_topup')->default(false);
            $table->integer('auto_topup_amount')->default(5000);
            $table->json('notify')->nullable();
            $table->string('status', 20)->default('active')->index(); // active, disabled, trashed
            $table->timestamp('trashed_at')->nullable();
            $table->string('trash_reason')->nullable();
            $table->string('trashed_by')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
