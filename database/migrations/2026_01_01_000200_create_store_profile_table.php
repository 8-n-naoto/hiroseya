<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 店舗情報。1 行しか持たない。
 *
 * このテーブルが店舗情報の唯一の正であり、フッター・アクセスページ・
 * 構造化データ (JSON-LD) はすべてここから出力する。
 * Googleビジネスプロフィールとの NAP（名称・住所・電話）一致が
 * この案件で最も効くSEO施策のため、二重管理を作らないこと。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_profile', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_kana')->nullable();
            $table->string('catch_copy')->nullable();
            $table->text('description')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('prefecture', 20)->nullable();
            $table->string('city', 60)->nullable();
            $table->string('address_line')->nullable();
            $table->string('building')->nullable();
            $table->string('tel', 20)->nullable();
            $table->string('fax', 20)->nullable();
            $table->string('email')->nullable();
            $table->unsignedSmallInteger('seats')->nullable();
            $table->string('parking')->nullable();
            $table->json('payment_methods')->nullable();
            $table->text('access_car')->nullable();
            $table->text('access_train')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_profile');
    }
};
