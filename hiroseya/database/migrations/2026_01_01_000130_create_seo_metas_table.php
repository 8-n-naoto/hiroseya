<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEOメタ情報。固定ページと各コンテンツを 1 テーブルで扱う。
 *
 *  - 固定ページ  : page_key に 'home' / 'menu' / 'access' などを入れる
 *  - コンテンツ  : seoable_type / seoable_id に料理・お知らせ・イベントを入れる
 *
 * 未設定でも空にはならないよう、モデル側で自動生成にフォールバックする。
 * 入力必須にすると運用で空欄や使い回しが量産され、かえってSEOを損なうため。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();
            $table->string('seoable_type')->nullable();
            $table->unsignedBigInteger('seoable_id')->nullable();
            $table->string('page_key', 50)->nullable();
            $table->string('title')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('keywords')->nullable();
            $table->string('canonical')->nullable();
            $table->string('robots', 50)->nullable()->comment('noindex 等。準備中モードが優先される');
            $table->string('og_title')->nullable();
            $table->string('og_description', 500)->nullable();
            $table->foreignId('og_image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamps();

            $table->index(['seoable_type', 'seoable_id']);
            $table->unique('page_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_metas');
    }
};
