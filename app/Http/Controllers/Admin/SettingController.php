<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\ActivityLog;
use App\Support\MailSettings;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * 各種設定。
 *
 * 設定の定義（型・初期値・ラベル・説明）は config/hiroseya.php にあり、
 * この画面はそれを読んでフォームを組み立てるだけ。設定を 1 つ増やすときに
 * 触るのが 1 か所で済み、画面と DB の食い違いが起きない。
 */
class SettingController extends Controller
{
    /** 画面に出すグループと、その説明。 */
    private const GROUPS = [
        'site' => ['サイト基本', 'サイト名、公開前の準備中モード、アクセス解析の設定です。'],
        'seo' => ['SEO', '検索結果に出るタイトルと説明文の既定値です。ページごとの設定は「ページのSEO」で行います。'],
        'access' => ['地図', 'アクセスページとトップページに出す地図の設定です。'],
        'mail' => ['メール', 'お問い合わせの通知メールと、お客様への自動返信メールに使う設定です。'],
        'reservation' => ['予約', 'サイト内の予約受付に関する設定です。予約機能は現在準備中のため、通常はOFFのままにしてください。'],
        'social' => ['SNS連携', 'SNSのAPI連携に関する設定です。リンクの表示は「SNS」の画面で行います。'],
    ];

    public function edit(?string $group, Settings $settings, MailSettings $mail): View
    {
        $group ??= 'site';

        abort_unless(array_key_exists($group, self::GROUPS), 404);

        return view('admin.settings.edit', [
            'group' => $group,
            'groups' => self::GROUPS,
            'definitions' => config("hiroseya.settings.{$group}", []),
            'values' => $settings->group($group),
            'mailConfigured' => $mail->configured(),
        ]);
    }

    public function update(string $group, Request $request, Settings $settings): RedirectResponse
    {
        abort_unless(array_key_exists($group, self::GROUPS), 404);

        $definitions = config("hiroseya.settings.{$group}", []);
        $input = $request->input('settings', []);
        $values = [];

        foreach ($definitions as $key => $definition) {
            $type = $definition['type'] ?? 'string';

            $values[$key] = match ($type) {
                'bool' => (bool) ($input[$key] ?? false),
                'int' => ($input[$key] ?? '') === '' ? null : (int) $input[$key],
                // 空欄で上書きすると、保存済みのパスワードが消える。
                // 入力があったときだけ差し替える。
                'encrypted' => ($input[$key] ?? '') === ''
                    ? $settings->string("{$group}.{$key}")
                    : (string) $input[$key],
                default => (string) ($input[$key] ?? ''),
            };
        }

        $this->validateGroup($group, $values);

        $settings->setMany($group, $values);

        ActivityLog::record('update', null, self::GROUPS[$group][0].'の設定を更新しました。');

        $message = '設定を保存しました。';

        if ($group === 'site' && ! $values['preparation_mode']) {
            $message .= ' 準備中モードを解除しました。公開サイトが検索エンジンに登録されるようになります。';
        }

        return back()->with('status', $message);
    }

    /**
     * グループごとの追加チェック。
     * 設定の型だけでは防げない「そのままだと動かない値」をここで止める。
     *
     * @param  array<string, mixed>  $values
     */
    private function validateGroup(string $group, array $values): void
    {
        $rules = match ($group) {
            'site' => [
                'site_name' => ['required', 'string', 'max:60'],
                'ga_measurement_id' => ['nullable', 'string', 'regex:/\AG-[A-Z0-9]+\z/'],
            ],
            'seo' => [
                'default_title' => ['required', 'string', 'max:120'],
                'default_description' => ['nullable', 'string', 'max:300'],
                'gbp_url' => ['nullable', 'url', 'max:500'],
            ],
            'access' => [
                'map_link' => ['nullable', 'url', 'max:1000'],
            ],
            'mail' => [
                'host' => ['nullable', 'string', 'max:191'],
                'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
                'from_address' => ['nullable', 'email:filter', 'max:191'],
                'reply_to' => ['nullable', 'email:filter', 'max:191'],
            ],
            'reservation' => [
                'accept_from_days' => ['nullable', 'integer', 'min:0', 'max:365'],
                'accept_until_days' => ['nullable', 'integer', 'min:1', 'max:365'],
                'max_party_size' => ['nullable', 'integer', 'min:1', 'max:200'],
            ],
            default => [],
        };

        if ($rules === []) {
            return;
        }

        // エラーの見出しを入力欄の名前（settings[...]）と揃える。
        // 揃えないと、画面上部にしかエラーが出ず、どの欄が悪いのか分からない。
        $prefixed = [];
        $attributes = [];

        foreach ($rules as $key => $rule) {
            $prefixed["settings.{$key}"] = $rule;
            $attributes["settings.{$key}"] = config("hiroseya.settings.{$group}.{$key}.label", $key);
        }

        validator(['settings' => $values], $prefixed, [
            'settings.ga_measurement_id.regex' => '測定IDは G- で始まる形式でご入力ください（例: G-XXXXXXXXXX）。',
        ], $attributes)->validate();
    }

    /**
     * テスト送信。
     *
     * 「設定を保存した」ことと「実際に届く」ことは別なので、
     * 公開前に必ず 1 通送って確認できるようにしている。
     */
    public function sendTestMail(Request $request, MailSettings $mail, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', 'email:filter', 'max:191'],
        ], [], ['to' => '送信先']);

        if (! $mail->configured()) {
            return back()->withErrors([
                'to' => 'SMTPホストと送信元アドレスを保存してから、テスト送信を行ってください。',
            ]);
        }

        $mail->apply();

        try {
            Mail::to($data['to'])->send(new TestMail($settings->string('site.site_name', '広瀬屋')));
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'to' => 'テストメールの送信に失敗しました。設定をご確認ください。（'.$e->getMessage().'）',
            ]);
        }

        return back()->with('status', $data['to'].' 宛にテストメールを送信しました。受信箱と迷惑メールフォルダをご確認ください。');
    }
}
