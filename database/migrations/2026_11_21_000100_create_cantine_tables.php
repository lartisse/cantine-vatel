<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_child', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $t->timestamp('linked_at')->nullable();
            $t->unique(['parent_id', 'child_id']);
        });

        Schema::create('link_tokens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $t->string('token', 64)->unique();
            $t->string('code', 12)->index();
            $t->timestamp('expires_at');
            $t->timestamp('used_at')->nullable();
            $t->timestamps();
        });

        Schema::create('dishes', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('category', 20)->index();
            $t->integer('price');
            $t->text('description')->nullable();
            $t->string('image_path')->nullable();
            $t->integer('kcal')->nullable();
            $t->json('allergens')->nullable();
            $t->json('diets')->nullable();
            $t->string('creator_name')->nullable(); // bonus « Chef de la semaine »
            $t->boolean('is_archived')->default(false);
            $t->softDeletes();
            $t->timestamps();
        });

        Schema::create('ingredients', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('unit', 10)->default('kg');
            $t->decimal('stock', 10, 2)->default(0);
            $t->decimal('threshold', 10, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('dish_ingredient', function (Blueprint $t) {
            $t->id();
            $t->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $t->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $t->decimal('quantity', 8, 3);
        });

        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $t->string('type', 20); // entree, sortie, perte
            $t->decimal('quantity', 10, 2);
            $t->string('reason')->nullable();
            $t->string('author')->nullable();
            $t->timestamps();
        });

        Schema::create('menus', function (Blueprint $t) {
            $t->id();
            $t->date('date')->unique();
            $t->boolean('is_published')->default(false);
            $t->timestamp('published_at')->nullable();
            $t->boolean('is_closed')->default(false);
            $t->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $t->foreignId('dish_id')->constrained();
            $t->integer('planned_portions')->default(60);
            $t->integer('antigaspi_price')->nullable(); // panier anti-gaspi (-50 %)
            $t->unique(['menu_id', 'dish_id']);
        });

        Schema::create('reservations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('menu_id')->constrained();
            $t->string('slot', 5);
            $t->string('status', 20)->default('paid')->index(); // paid, served, missed, cancelled
            $t->string('code', 6)->index();
            $t->string('qr_token', 40)->unique();
            $t->integer('total');
            $t->string('class_snapshot', 30)->nullable();
            $t->foreignId('booked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('served_at')->nullable();
            $t->json('allergy_conflict')->nullable();
            $t->foreignId('allergy_checked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('allergy_checked_at')->nullable();
            $t->string('ate_all', 10)->nullable(); // tout, moitie, peu
            $t->timestamps();
        });

        Schema::create('reservation_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('dish_id')->constrained();
            $t->integer('quantity');
            $t->integer('unit_price');
        });

        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('menu_id')->constrained();
            $t->foreignId('dish_id')->nullable()->constrained();
            $t->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedTinyInteger('rating');
            $t->text('comment')->nullable();
            $t->json('tags')->nullable();
            $t->string('status', 20)->default('visible'); // visible, flagged, hidden
            $t->string('flag_reason')->nullable();
            $t->json('flag_words')->nullable();
            $t->boolean('approved')->default(false);
            $t->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 20); // topup, meal, refund, adjustment
            $t->integer('amount');
            $t->string('label');
            $t->string('reference')->nullable();
            $t->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name');
            $t->string('kind', 30);
            $t->string('color', 10)->nullable();
            $t->string('logo', 10)->nullable();
            $t->json('fields');
            $t->boolean('is_active_test')->default(false);
            $t->boolean('is_active_live')->default(false);
            $t->timestamp('tested_test_at')->nullable();
            $t->boolean('test_ok_test')->nullable();
            $t->timestamp('tested_live_at')->nullable();
            $t->boolean('test_ok_live')->nullable();
            $t->timestamps();
        });

        Schema::create('payment_credentials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $t->string('environment', 10); // test, live
            $t->string('key_name', 40);
            $t->text('value'); // chiffrée (Crypt::encryptString)
            $t->string('prefix', 20)->nullable();
            $t->string('last4', 4);
            $t->timestamps();
            $t->unique(['payment_method_id', 'environment', 'key_name']);
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('beneficiary_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $t->integer('amount');
            $t->string('status', 20)->default('success');
            $t->string('environment', 10)->default('test');
            $t->string('provider_reference')->nullable();
            $t->timestamps();
        });

        Schema::create('deletion_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->text('reason')->nullable();
            $t->string('status', 20)->default('pending'); // pending, accepted, refused, cancelled
            $t->text('response_message')->nullable();
            $t->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('actor_name')->nullable();
            $t->string('actor_role', 20)->nullable();
            $t->string('action', 50)->index();
            $t->string('target_type', 50)->nullable();
            $t->unsignedBigInteger('target_id')->nullable();
            $t->string('target_label')->nullable();
            $t->text('details')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('app_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('body')->nullable();
            $t->string('url')->nullable();
            $t->string('kind', 30)->default('info'); // info, allergy, low_balance, served, menu, review
            $t->string('icon', 30)->default('bell');
            $t->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'app_notifications', 'audit_logs', 'deletion_requests', 'payments', 'payment_credentials', 'payment_methods',
            'transactions', 'reviews', 'reservation_items', 'reservations', 'menu_items', 'menus', 'stock_movements', 'dish_ingredient',
            'ingredients', 'dishes', 'link_tokens', 'parent_child'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
