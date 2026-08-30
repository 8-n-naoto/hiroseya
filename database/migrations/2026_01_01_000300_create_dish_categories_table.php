<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 料理カテゴリ。
 *
 * 初期データは店舗が実際に看板で使っている分類をそのまま採用している
 * （温かい麺 / 冷たい麺 / 煮込み / 丼もの / 定食物 / 麺セット温 / 麺セット冷 / 冬限定）。
 * 新しい分類を管理画面から追加・並べ替えできる。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dish_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('dish_categories')->nullOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100)->unique()->comment('URLに使うため英数字で入力する');
            $table->text('description')->nullable();
            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('service_type', 20)->default('dine_in')->comment('dine_in|takeout');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['service_type', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_categories');
    }
};
