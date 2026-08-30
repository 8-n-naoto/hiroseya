<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 営業時間。曜日別に構造化して持つ。
 *
 * 「11:00〜14:00, 17:00〜20:00（水曜定休）」を 1 つのテキストにすると
 * 構造化データ (openingHoursSpecification) を出力できず、
 * 「本日営業中」の判定もできないため、必ず行に分ける。
 * 昼の部・夜の部のように 1 曜日に複数行を持たせられる。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week')->comment('0=日曜 ... 6=土曜');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false)->comment('定休日');
            $table->string('label', 50)->nullable()->comment('昼の部 / 夜の部 など');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['day_of_week', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_hours');
    }
};
