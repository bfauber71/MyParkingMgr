<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('property_id');
            $table->string('name', 255);
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->tinyInteger('position')->unsigned();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->unique(['property_id', 'position'], 'unique_property_position');
            $table->index('property_id', 'idx_property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_contacts');
    }
};
