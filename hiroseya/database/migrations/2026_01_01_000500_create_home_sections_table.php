<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * トップページ（LP）のセクション。
 *
 * セクションの「型」は config/hiroseya.php で固定し、
 * 中身（画像・見出し・本文・表示/非表示・順序）だけを管理画面から変えられるようにする。
 * 過度なページビルダーにはしない。
 *
 * options は型ごとに使うキーを固定して扱う（表示件数など）。
 * JSON は検索もバリデーションも弱いため、自由項目は作らないこと。
 *
 * media_id はPC用、media_sp_id はスマートフォン用。
 * PC用の横長画像をSPで切り抜くと料理が切れるため、別々に登録できるようにしている。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique()->comment('hero / catch / about ...');
            $table->string('type', 50);
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('body')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('media_sp_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('options')->nullable();
            $table->timestamps();

            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};
