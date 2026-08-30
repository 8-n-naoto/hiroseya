<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 特定日の予約枠の上書き（貸切・満席・臨時休業・時間変更）。
 *
 * starts_at が null の行はその日全体への指定。
 * 注意: MySQL・SQLite とも UNIQUE 制約は NULL を重複として扱わないため、
 * 「その日全体」の行が複数作られないことはアプリ側で担保する
 * （ReservationSlotOverride::forWholeDay() を参照）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_slot_overrides', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('starts_at')->nullable()->comment('null ならその日全体');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['date', 'starts_at']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_slot_overrides');
    }
};
