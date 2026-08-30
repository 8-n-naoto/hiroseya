<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\ContactRequest;
use App\Services\ContactService;
use App\Support\Seo;
use App\Support\SiteContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(Request $request, Seo $seo, SiteContext $site): View
    {
        // 表示した時刻を控えておき、送信までが極端に速い自動投稿をはじく。
        $request->session()->put('contact_form_opened_at', time());

        $seo->page('contact')
            ->title('お問い合わせ')
            ->breadcrumbs([['お問い合わせ', route('contact.create')]]);

        return view('site.contact.create', ['store' => $site->store()]);
    }

    public function store(ContactRequest $request, ContactService $contacts): RedirectResponse
    {
        $contact = $contacts->receive($request->validated(), $request);

        $request->session()->forget('contact_form_opened_at');

        // 完了ページを直接開かれても内容が出ないよう、1 回限りのフラグで守る。
        return redirect()->route('contact.complete')
            ->with('contact_completed', $contact->created_at?->toIso8601String() ?? true);
    }

    public function complete(Request $request, Seo $seo): View|RedirectResponse
    {
        if (! $request->session()->get('contact_completed')) {
            return redirect()->route('contact.create');
        }

        $seo->page('contact-complete')
            ->title('お問い合わせを承りました')
            ->noindex()
            ->breadcrumbs([
                ['お問い合わせ', route('contact.create')],
                ['送信完了', null],
            ]);

        return view('site.contact.complete');
    }
}
