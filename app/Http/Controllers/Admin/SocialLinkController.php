<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SocialLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SNS のリンク。
 *
 * リンクの表示と API 連携は完全に分けてある。
 * API 連携はトークンの有効期限管理が必要で、失効した瞬間にフィードが止まる。
 * リンクだけならその心配が無く、実際の集客効果もほぼ変わらない。
 */
class SocialLinkController extends Controller
{
    public function index(): View
    {
        // config にあるのに DB に無いプラットフォームも行として出す。
        foreach (SocialLink::PLATFORMS as $platform => $label) {
            SocialLink::firstOrCreate(['platform' => $platform], [
                'display_name' => $label,
                'is_visible' => false,
                'sort_order' => array_search($platform, array_keys(SocialLink::PLATFORMS), true),
            ]);
        }

        return view('admin.social-links.index', [
            'links' => SocialLink::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'links' => ['required', 'array'],
            'links.*.url' => ['nullable', 'url', 'max:500'],
            'links.*.display_name' => ['nullable', 'string', 'max:60'],
        ], [
            'links.*.url.url' => 'URL は https:// から始まる形式でご入力ください。',
        ]);

        foreach ($request->input('links', []) as $id => $values) {
            $link = SocialLink::find($id);

            if (! $link) {
                continue;
            }

            $url = $values['url'] ?? null;

            $link->fill([
                'display_name' => $values['display_name'] ?? null,
                'url' => $url ?: null,
                // URL が無いのに「表示する」にはできない。空リンクを出さないため。
                'is_visible' => filled($url) && ! empty($values['is_visible']),
                'sort_order' => (int) ($values['sort_order'] ?? $link->sort_order),
            ])->save();
        }

        ActivityLog::record('update', null, 'SNSのリンクを更新しました。');

        return back()->with('status', 'SNSの設定を保存しました。');
    }
}
