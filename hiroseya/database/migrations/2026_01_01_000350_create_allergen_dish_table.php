<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allergen_dish', function (Blueprint $table) {
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->foreignId('allergen_id')->constrained()->cascadeOnDelete();

            $table->primary(['dish_id', 'allergen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allergen_dish');
    }
};
