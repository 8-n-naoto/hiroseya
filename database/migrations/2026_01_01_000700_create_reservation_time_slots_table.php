<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 予約枠の基本パターン（曜日別）。
 *
 * capacity は「在庫」ではなく「Webで受け付ける上限」。
 * 未確認 + 確定 の合計人数が上限に達した枠はフォームで選べなくし、
 * 「満席のためお電話ください」と案内する。
 * 店舗は承認時に上限を超える判断もできる（上限は目安であって拘束ではない）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_time_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week')->comment('0=日曜 ... 6=土曜');
            $table->time('starts_at');
            $table->unsignedSmallInteger('capacity')->default(10);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['day_of_week', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_time_slots');
    }
};
