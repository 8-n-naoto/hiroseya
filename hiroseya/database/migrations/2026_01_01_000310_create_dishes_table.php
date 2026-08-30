<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 料理。価格はこのテーブルに持たず dish_variants に分ける。
 *
 * 実際のメニュー表に「みそ煮込み / みそ煮込みセット」「みそかつ定食 / 単品」
 * 「かつなべ定食 / うどん入り」「ミニ丼」が存在するため、
 * price を 1 カラムにすると初日から破綻する。
 *
 * has_detail_page:
 *   料理は 100 品を超え、多くは写真と価格しかない。全品を個別URLにすると
 *   中身の薄いページが大量生成されてSEO上むしろ不利になるため、
 *   説明文を書ける料理だけ詳細ページを持たせる。
 *
 * available_from / available_to:
 *   冬季限定・夏季おすすめ・9月限定が常態のため、提供期間による自動出し分けが必須。
 *   手動切替にすると必ず出しっぱなしになる。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('dish_categories')->nullOnDelete();
            $table->string('name', 150);
            $table->string('name_kana', 150)->nullable();
            $table->string('slug', 150)->unique();
            $table->string('description', 500)->nullable();
            $table->text('body')->nullable();
            $table->foreignId('main_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_limited')->default(false);
            $table->boolean('is_sold_out')->default(false);
            $table->date('available_from')->nullable();
            $table->date('available_to')->nullable();
            $table->boolean('has_detail_page')->default(false);
            $table->string('status', 20)->default('draft')->comment('draft|published');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'sort_order']);
            $table->index(['is_recommended', 'status']);
            $table->index(['available_from', 'available_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dishes');
    }
};
