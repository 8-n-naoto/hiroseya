<?php

namespace App\Support;

use App\Models\SocialLink;
use App\Models\StoreProfile;
use App\Services\BusinessHourService;
use Illuminate\Support\Collection;

/**
 * 公開サイトの全ページが必要とする共通データ。
 *
 * 店舗情報・営業時間・SNS・グローバルナビは、ヘッダーとフッターが
 * どのページでも参照する。コントローラーごとに毎回 with() で渡すと
 * 必ず渡し忘れが出るため、リクエスト内で 1 インスタンスに固定して
 * レイアウトから直接引く。
 */
class SiteContext
{
    private ?StoreProfile $store = null;

    /** @var Collection<int, SocialLink>|null */
    private ?Collection $social = null;

    public function __construct(
        private readonly Settings $settings,
        private readonly BusinessHourService $hours,
    ) {}

    public function store(): StoreProfile
    {
        return $this->store ??= StoreProfile::current();
    }

    public function hours(): BusinessHourService
    {
        return $this->hours;
    }

    public function settings(): Settings
    {
        return $this->settings;
    }

    /** @return Collection<int, SocialLink> */
    public function socialLinks(): Collection
    {
        return $this->social ??= SocialLink::visible()->get();
    }

    public function siteName(): string
    {
        return $this->settings->string('site.site_name', $this->store()->name);
    }

    public function siteNameEn(): string
    {
        return $this->settings->string('site.site_name_en', 'Hiroseya');
    }

    /**
     * グローバルナビ。予約は設定が ON のときだけ出す。
     *
     * @return array<int, array{route: string, label: string, reading: string}>
     */
    public function navigation(): array
    {
        $items = [
            ['route' => 'home', 'label' => 'ホーム', 'reading' => 'Home'],
            ['route' => 'menu.index', 'label' => 'お品書き', 'reading' => 'Menu'],
            ['route' => 'news.index', 'label' => 'お知らせ', 'reading' => 'News'],
            ['route' => 'events.index', 'label' => 'イベント', 'reading' => 'Event'],
            ['route' => 'access', 'label' => 'アクセス', 'reading' => 'Access'],
            ['route' => 'contact.create', 'label' => 'お問い合わせ', 'reading' => 'Contact'],
        ];

        if ($this->settings->reservationEnabled() && \Illuminate\Support\Facades\Route::has('reservation.create')) {
            $items[] = ['route' => 'reservation.create', 'label' => 'ご予約', 'reading' => 'Reservation'];
        }

        return $items;
    }

    /** Google Analytics の測定ID。準備中モードの間は計測しない。 */
    public function analyticsId(): ?string
    {
        if ($this->settings->inPreparation()) {
            return null;
        }

        $id = trim($this->settings->string('site.ga_measurement_id'));

        return $id !== '' ? $id : null;
    }

    /**
     * Search Console の所有権確認用の値。
     *
     * 店舗の方は Google が案内する <meta ...> をそのまま貼ることが多いので、
     * タグごと貼られた場合は content の中身だけを取り出す。
     * こうしないと meta の中に meta が入り、確認に失敗する。
     */
    public function searchConsoleTag(): ?string
    {
        $value = trim($this->settings->string('site.gsc_verification'));

        if ($value === '') {
            return null;
        }

        if (preg_match('/content=["\']([^"\']+)["\']/i', $value, $matches)) {
            return $matches[1];
        }

        return strip_tags($value) !== $value ? null : $value;
    }
}
