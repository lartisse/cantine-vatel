<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Clés d'accès à l'API publique (consommation par d'autres applications et services). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $t) {
            $t->id();
            $t->string('name');                         // application ou service consommateur
            $t->string('prefix', 16)->index();          // début de la clé, affiché pour la reconnaître
            $t->string('key_hash', 64)->unique();       // SHA-256 de la clé : la clé en clair n'est jamais stockée
            $t->string('last4', 4);
            $t->json('scopes');                         // portées autorisées (menus:read, orders:write…)
            $t->unsignedInteger('rate_limit')->default(60); // requêtes par minute
            $t->boolean('is_active')->default(true);
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->string('last_used_ip', 45)->nullable();
            $t->unsignedBigInteger('requests_count')->default(0);
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
