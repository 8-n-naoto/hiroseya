<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SNS。リンク表示と API 連携を完全に分離する。
 *
 * このテーブルに行があればリンクは必ず表示され、
 * api_enabled が true のときだけ投稿取得を試みる。
 * 取得は cron でキャッシュに書き込み、表示側はキャッシュだけを読むため、
 * API障害やトークン失効時もページは通常表示される（フィード部分だけが消える）。
 *
 * api_credentials は暗号化して保存する（モデルの encrypted:array キャスト）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 30)->unique()->comment('instagram / x / facebook / tiktok / youtube');
            $table->string('display_name', 60)->nullable();
            $table->string('url')->nullable();
            $table->foreignId('icon_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_visible')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('api_enabled')->default(false);
            $table->text('api_credentials')->nullable();
            $table->timestamps();

            $table->index(['is_visible', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_links');
    }
};
