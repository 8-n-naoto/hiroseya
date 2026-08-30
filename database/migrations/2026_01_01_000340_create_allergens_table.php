<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * アレルゲン。特定原材料8品目を初期投入する。
 *
 * 入力は任意で、未入力の料理では表示自体を出さない。
 * 掲載した時点で正確性の責任が生じるため、表示する場合は
 * 「同一調理場のため微量混入の可能性があります」の注記を必ず添えること。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allergens', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->foreignId('icon_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allergens');
    }
};
