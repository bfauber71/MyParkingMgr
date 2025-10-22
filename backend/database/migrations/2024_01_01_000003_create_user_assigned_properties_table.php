<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_assigned_properties', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->uuid('property_id');
            $table->timestamp('assigned_at')->useCurrent();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->unique(['user_id', 'property_id'], 'unique_user_property');
            $table->index('user_id', 'idx_user_id');
            $table->index('property_id', 'idx_property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_assigned_properties');
    }
};
