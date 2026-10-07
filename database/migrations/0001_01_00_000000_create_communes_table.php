<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Se ejecuta antes de users porque users.commune_id depende de esta tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->string('type', 20);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();
        });

        Schema::create('commune_neighbors', function (Blueprint $table) {
            $table->foreignId('commune_id')->constrained('communes')->cascadeOnDelete();
            $table->foreignId('neighbor_id')->constrained('communes')->cascadeOnDelete();
            $table->primary(['commune_id', 'neighbor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commune_neighbors');
        Schema::dropIfExists('communes');
    }
};
