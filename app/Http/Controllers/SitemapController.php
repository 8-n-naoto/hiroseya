<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use Illuminate\Http\Response;

/**
 * sitemap.xml を動的に出し分ける。
 *
 * 準備中モードがONの間は空のサイトマップを返し、まだ存在しない・
 * 完成していないページが検索エンジンに登録されるのを防ぐ。
 *
 * 公開サイトのページ（料理詳細・お知らせ・イベントなど）は Phase 4 で
 * ルーティングが揃い次第、ここに追加していく。今はトップページのみ。
 */
class SitemapController extends Controller
{
    public function __invoke(Settings $settings): Response
    {
        $urls = [];

        if (! $settings->inPreparation()) {
            $urls[] = [
                'loc' => url('/'),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ];

            // TODO(Phase 4): 料理詳細（has_detail_page=true）・お知らせ・イベント・
            // 固定ページ（menu / access / contact / reservation）のURLをここに追加する。
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
