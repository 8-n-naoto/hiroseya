<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Contact;
use App\Services\ContactService;
use App\Support\MailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * お問い合わせ対応。
 *
 * 「未対応が何件あるか」が常に分かることを最優先にしている。
 * 返信は必ず contact_replies に残し、送信に失敗した返信も消さずに残す
 * （書き直しを強いると、送れたかどうかも分からなくなる）。
 */
class ContactController extends Controller
{
    public function index(Request $request, MailSettings $mail): View
    {
        $status = $request->string('status')->toString();

        return view('admin.contacts.index', [
            'contacts' => Contact::query()
                ->with('assignee')
                ->when($status !== '', fn ($q) => $q->where('status', $status))
                ->when($status === '', fn ($q) => $q->orderByRaw(
                    // 未対応・対応中を先に、その中で新しい順。
                    "CASE status WHEN 'pending' THEN 0 WHEN 'in_progress' THEN 1 ELSE 2 END"
                ))
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                    ->where('name', 'like', '%'.$request->string('q').'%')
                    ->orWhere('email', 'like', '%'.$request->string('q').'%')
                    ->orWhere('subject', 'like', '%'.$request->string('q').'%')
                    ->orWhere('body', 'like', '%'.$request->string('q').'%')))
                ->latest('created_at')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $request->only(['status', 'q']),
            'counts' => [
                'pending' => Contact::pending()->count(),
                'open' => Contact::open()->count(),
            ],
            'mailConfigured' => $mail->configured(),
            'notifyConfigured' => $mail->notifyRecipients() !== [],
        ]);
    }

    public function show(Contact $contact, MailSettings $mail): View
    {
        return view('admin.contacts.show', [
            'contact' => $contact->load(['replies.user', 'assignee']),
            'mailConfigured' => $mail->configured(),
        ]);
    }

    /** ステータスと社内メモの更新。 */
    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_column(ContactStatus::cases(), 'value'))],
            'admin_memo' => ['nullable', 'string', 'max:5000'],
        ], [], ['status' => '対応状況', 'admin_memo' => '社内メモ']);

        $contact->forceFill([
            'status' => $data['status'],
            'status_changed_at' => now(),
            'assigned_to' => $contact->assigned_to ?? $request->user()?->id,
            'admin_memo' => $data['admin_memo'] ?? null,
        ])->save();

        ActivityLog::record('status_change', $contact, "お問い合わせ #{$contact->id} を「{$contact->status->label()}」にしました。");

        return back()->with('status', '対応状況を更新しました。');
    }

    /** お客様への返信。送信結果は必ず記録する。 */
    public function reply(Request $request, Contact $contact, ContactService $contacts): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'mark_done' => ['boolean'],
        ], [], ['body' => '返信内容']);

        $reply = $contacts->reply($contact, $data['body'], $request->user()?->id);

        if ($request->boolean('mark_done')) {
            $contact->changeStatus(ContactStatus::Done);
        } elseif ($contact->status === ContactStatus::Pending) {
            $contact->changeStatus(ContactStatus::InProgress);
        }

        ActivityLog::record('update', $contact, "お問い合わせ #{$contact->id} に返信しました。");

        if (! $reply->wasSent()) {
            return back()->withErrors([
                'reply' => '返信メールの送信に失敗しました。メール設定をご確認のうえ、下の履歴から再送してください。'
                    .'（内容は保存されています）',
            ]);
        }

        return back()->with('status', '返信を送信しました。');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        ActivityLog::record('delete', null, "お問い合わせ #{$contact->id} を削除しました。");

        return redirect()->route('admin.contacts.index')->with('status', 'お問い合わせを削除しました。');
    }
}
