<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 型付きの設定テーブル。
 *
 * 置き場所の使い分け:
 *  - 1件しかなく項目が固定で、公開ページの表示に直接使う  -> 専用テーブル (store_profile)
 *  - 件数が可変で並べ替え・個別の表示制御がある            -> 独立テーブル (social_links / business_hours)
 *  - ON/OFF や単一の文字列で、画面をまたいで参照される      -> このテーブル
 *
 * 初期値と型は config/hiroseya.php の settings 配列が正で、SettingSeeder が投入する。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50);
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string')->comment('string|text|bool|int|json|encrypted');
            $table->timestamps();

            $table->unique(['group', 'key']);
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
