<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SeoMeta;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 固定ページのSEO。
 *
 * 未入力でも困らない作りにしてある（空なら自動生成に戻る）。
 * 入力を必須にすると、運用では必ず全ページ同じ文言が使い回され、
 * かえって重複タイトル・重複ディスクリプションを量産する。
 */
class SeoMetaController extends Controller
{
    /** 編集できる固定ページ。ここに無いページはモデル側の自動生成に任せる。 */
    private const PAGES = [
        'home' => ['トップページ', '/'],
        'menu' => ['お品書き（店内）', '/menu'],
        'takeout' => ['お持ち帰り', '/takeout'],
        'news' => ['お知らせ一覧', '/news'],
        'events' => ['イベント一覧', '/events'],
        'access' => ['アクセス・営業時間', '/access'],
        'contact' => ['お問い合わせ', '/contact'],
        'privacy' => ['プライバシーポリシー', '/privacy'],
    ];

    public function index(Settings $settings): View
    {
        $metas = SeoMeta::query()->whereNotNull('page_key')->get()->keyBy('page_key');

        return view('admin.seo.index', [
            'pages' => self::PAGES,
            'metas' => $metas,
            'defaults' => [
                'title' => $settings->string('seo.default_title'),
                'suffix' => $settings->string('seo.title_suffix'),
                'description' => $settings->string('seo.default_description'),
            ],
            'inPreparation' => $settings->inPreparation(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'pages' => ['required', 'array'],
            'pages.*.title' => ['nullable', 'string', 'max:191'],
            'pages.*.description' => ['nullable', 'string', 'max:500'],
            'pages.*.og_image_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ], [
            'pages.*.title.max' => 'タイトルは 191 文字以内でご入力ください。',
            'pages.*.description.max' => 'ディスクリプションは 500 文字以内でご入力ください。',
        ]);

        foreach ($request->input('pages', []) as $key => $values) {
            if (! array_key_exists($key, self::PAGES)) {
                continue;
            }

            SeoMeta::updateOrCreate(['page_key' => $key], [
                'title' => $values['title'] ?: null,
                'description' => $values['description'] ?: null,
                'og_image_media_id' => $values['og_image_media_id'] ?: null,
            ]);
        }

        ActivityLog::record('update', null, 'ページのSEO設定を更新しました。');

        return back()->with('status', 'SEO設定を保存しました。');
    }
}
