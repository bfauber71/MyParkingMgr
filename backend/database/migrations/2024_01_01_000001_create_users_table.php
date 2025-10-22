<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username', 255)->unique();
            $table->string('password', 255);
            $table->enum('role', ['admin', 'user', 'operator'])->default('user');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('username', 'idx_username');
            $table->index('role', 'idx_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
