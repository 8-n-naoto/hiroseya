<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * イベント。お知らせとほぼ同型だが、開催期間を持ち
 * 「開催中 / 終了」の出し分けと構造化データ (Event) の出力があるため別テーブルにしている。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('excerpt', 500)->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('main_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_on']);
            $table->index(['starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
