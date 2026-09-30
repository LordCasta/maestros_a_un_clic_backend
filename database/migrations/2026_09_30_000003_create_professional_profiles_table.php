<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->decimal('hourly_rate', 12, 2)->nullable();
            $table->unsignedSmallInteger('service_radius_km')->default(10);
            $table->unsignedSmallInteger('buffer_minutes')->default(30);
            $table->timestamps();
        });

        Schema::create('professional_category', function (Blueprint $table) {
            $table->foreignId('professional_profile_id')->constrained('professional_profiles')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->primary(['professional_profile_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_category');
        Schema::dropIfExists('professional_profiles');
    }
};
