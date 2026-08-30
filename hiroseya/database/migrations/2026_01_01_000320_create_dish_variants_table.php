<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 料理の価格バリエーション。この設計が今回の要になる。
 *
 * label      : 単品 / セット / ミニ / うどん入り / 三枚 など
 * price      : 税込を正とする整数（円）。小数は扱わない
 * service_type:
 *   店内飲食と持ち帰りで価格が違う（持ち帰りはパック代込）ため、
 *   提供区分をここに持たせる。こうすると同じ「かつ丼」を
 *   店内 880 円・持ち帰り 900 円として 1 レコードで管理でき、
 *   写真・説明・SEO を共有したまま一覧を分けて表示できる。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dish_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60)->nullable()->comment('単品 / セット / ミニ など。1種類だけなら空でよい');
            $table->unsignedInteger('price')->comment('税込価格（円）');
            $table->unsignedInteger('price_excluding_tax')->nullable();
            $table->string('service_type', 20)->default('dine_in')->comment('dine_in|takeout');
            $table->boolean('is_default')->default(false)->comment('一覧に代表として出す価格');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['dish_id', 'service_type', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_variants');
    }
};
