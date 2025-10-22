<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('property', 255);
            $table->string('tag_number', 100)->nullable();
            $table->string('plate_number', 100)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('year', 10)->nullable();
            $table->string('apt_number', 50)->nullable();
            $table->string('owner_name', 255)->nullable();
            $table->string('owner_phone', 50)->nullable();
            $table->string('owner_email', 255)->nullable();
            $table->string('reserved_space', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->foreign('property')->references('name')->on('properties')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->index('property', 'idx_property');
            $table->index('tag_number', 'idx_tag_number');
            $table->index('plate_number', 'idx_plate_number');
            $table->index('owner_name', 'idx_owner_name');
            $table->index('apt_number', 'idx_apt_number');
        });
        
        DB::statement('ALTER TABLE vehicles ADD FULLTEXT INDEX ft_search (tag_number, plate_number, make, model, owner_name, apt_number)');
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
