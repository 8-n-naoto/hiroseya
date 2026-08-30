<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * トップページに出す「おすすめ料理」の選択と並び。
 *
 * home_sections.options に料理IDの配列を入れる手もあるが、
 * それだと削除された料理の掃除ができず、並べ替えのたびにJSONを書き換えることになる。
 * 独立テーブルにして外部キーで守る。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_recommended_dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->unique('dish_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_recommended_dishes');
    }
};
