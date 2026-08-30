<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 料理の追加画像（メイン画像は dishes.main_media_id）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dish_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->unique(['dish_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_media');
    }
};
