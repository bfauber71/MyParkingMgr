<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->nullable();
            $table->string('username', 255);
            $table->string('action', 100);
            $table->string('entity_type', 50);
            $table->uuid('entity_id')->nullable();
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('user_id', 'idx_user_id');
            $table->index('username', 'idx_username');
            $table->index('action', 'idx_action');
            $table->index('entity_type', 'idx_entity_type');
            $table->index('created_at', 'idx_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
