<x-layouts.admin title="ダッシュボード">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-stone-200 bg-white p-5">
            <p class="text-xs text-stone-500">未確認の予約</p>
            <p class="mt-1 text-2xl font-semibold {{ $pendingReservationCount > 0 ? 'text-amber-600' : 'text-stone-900' }}">
                {{ $reservationsEnabled ? $pendingReservationCount.'件' : '予約機能OFF' }}
            </p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-5">
            <p class="text-xs text-stone-500">未対応の問い合わせ</p>
            <p class="mt-1 text-2xl font-semibold {{ $openContactCount > 0 ? 'text-amber-600' : 'text-stone-900' }}">
                {{ $openContactCount }}件
            </p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-5">
            <p class="text-xs text-stone-500">alt未入力の画像</p>
            <p class="mt-1 text-2xl font-semibold {{ $missingAltCount > 0 ? 'text-amber-600' : 'text-stone-900' }}">
                {{ $missingAltCount }}件
            </p>
            <a href="{{ route('admin.media.index', ['missing_alt' => 1]) }}" class="mt-1 inline-block text-xs text-stone-500 underline">
                確認する
            </a>
        </div>
    </div>

    @if ($reservationsEnabled && $pendingReservations->isNotEmpty())
        <section class="mt-8">
            <h2 class="mb-3 text-sm font-semibold text-stone-700">直近の未確認予約</h2>
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($pendingReservations as $reservation)
                            <tr>
                                <td class="px-4 py-3 text-stone-600">{{ $reservation->summaryLabel() }}</td>
                                <td class="px-4 py-3 text-stone-900">{{ $reservation->name }} 様</td>
                                <td class="px-4 py-3 text-right text-stone-400">{{ $reservation->reservation_no }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($openContacts->isNotEmpty())
        <section class="mt-8">
            <h2 class="mb-3 text-sm font-semibold text-stone-700">未対応の問い合わせ</h2>
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($openContacts as $contact)
                            <tr>
                                <td class="px-4 py-3 text-stone-600">{{ $contact->created_at->format('n/j') }}</td>
                                <td class="px-4 py-3 text-stone-900">{{ $contact->name }} 様</td>
                                <td class="px-4 py-3 text-stone-500">{{ \Illuminate\Support\Str::limit($contact->subject, 30) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">
                                        {{ $contact->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="mt-8">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-stone-700">公開前チェックリスト</h2>
            <span class="text-xs {{ $inPreparation ? 'text-amber-600' : 'text-green-600' }}">
                {{ $inPreparation ? '準備中モード：ON' : '準備中モード：OFF（公開中）' }}
            </span>
        </div>
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white">
            <ul class="divide-y divide-stone-100 text-sm">
                @foreach ($checklist as $item)
                    <li class="flex items-center gap-3 px-4 py-3">
                        @if ($item['done'] === true)
                            <span class="text-green-600">✔</span>
                        @elseif ($item['done'] === false)
                            <span class="text-amber-600">未</span>
                        @else
                            <span class="text-stone-300">-</span>
                        @endif
                        <span class="{{ $item['done'] === false ? 'text-stone-900' : 'text-stone-500' }}">
                            {{ $item['label'] }}
                        </span>
                        @if ($item['done'] === null)
                            <span class="ml-auto text-xs text-stone-400">要確認</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        <p class="mt-2 text-xs text-stone-400">
            「要確認」は自動判定できない項目です。準備が整ったら設定画面から準備中モードをOFFにしてください（設定画面は今後追加予定）。
        </p>
    </section>
</x-layouts.admin>
