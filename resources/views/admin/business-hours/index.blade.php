@php use App\Models\BusinessHour; @endphp

<x-layouts.admin title="営業時間・臨時休業">
    <x-admin.page-header title="営業時間・臨時休業"
        description="ここで入力した内容が、サイトの表示と検索エンジンに渡す営業時間の両方になります。曜日ごとに「昼の部」「夜の部」の2つまで設定できます。通し営業の場合は1つだけ入力してください。" />

    @if ($inPreparation)
        <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            初期値は仮の営業時間（水曜定休・11:00〜14:00 / 17:00〜20:00）です。
            公開前に必ず実際の営業時間へ更新してください。誤った営業時間が検索結果に載ると、
            お客様が閉店中に来店してしまいます。
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
        <form method="POST" action="{{ route('admin.business-hours.update') }}">
            @csrf @method('PUT')

            <x-admin.card title="曜日ごとの営業時間">
                <div class="space-y-5">
                    @for ($day = 0; $day <= 6; $day++)
                        @php
                            $slots = ($rows[$day] ?? collect())->reject(fn ($r) => $r->is_closed)->values();
                            $isClosed = ! isset($rows[$day]) || $rows[$day]->every(fn ($r) => $r->is_closed);
                        @endphp

                        <div class="rounded-md border border-stone-200 p-4" x-data="{ closed: {{ $isClosed ? 'true' : 'false' }} }">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-sm font-semibold text-stone-800">{{ BusinessHour::DAY_LABELS[$day] }}曜日</p>
                                <label class="flex items-center gap-2 text-sm text-stone-600">
                                    <input type="hidden" name="days[{{ $day }}][closed]" value="0">
                                    <input type="checkbox" name="days[{{ $day }}][closed]" value="1" x-model="closed"
                                        class="h-4 w-4 rounded border-stone-300">
                                    定休日
                                </label>
                            </div>

                            <div class="space-y-3" x-show="!closed">
                                @for ($i = 0; $i < 2; $i++)
                                    @php $slot = $slots->get($i); @endphp
                                    <div class="grid grid-cols-12 items-end gap-2">
                                        <div class="col-span-4">
                                            <label class="mb-1 block text-xs text-stone-500">名称</label>
                                            <x-admin.input name="days[{{ $day }}][slots][{{ $i }}][label]"
                                                value="{{ $slot?->label ?? ($i === 0 ? '昼の部' : '夜の部') }}"
                                                placeholder="昼の部" />
                                        </div>
                                        <div class="col-span-4">
                                            <label class="mb-1 block text-xs text-stone-500">開店</label>
                                            <x-admin.input type="time" name="days[{{ $day }}][slots][{{ $i }}][opens_at]"
                                                value="{{ $slot ? substr((string) $slot->opens_at, 0, 5) : '' }}" />
                                        </div>
                                        <div class="col-span-4">
                                            <label class="mb-1 block text-xs text-stone-500">閉店</label>
                                            <x-admin.input type="time" name="days[{{ $day }}][slots][{{ $i }}][closes_at]"
                                                value="{{ $slot ? substr((string) $slot->closes_at, 0, 5) : '' }}" />
                                        </div>
                                    </div>
                                @endfor
                                <p class="text-xs text-stone-400">
                                    開店・閉店の両方が空欄の枠は保存されません。夜の部が無い曜日は空欄のままにしてください。
                                </p>
                            </div>
                        </div>
                    @endfor
                </div>

                <div class="mt-6">
                    <x-admin.button>営業時間を保存する</x-admin.button>
                </div>
            </x-admin.card>
        </form>

        <div class="space-y-6">
            <x-admin.card title="臨時休業・時間変更"
                description="お盆・年末年始・貸切など、特定の日だけの変更を登録します。サイトの表示と検索エンジンの営業時間の両方に反映されます。">
                <form method="POST" action="{{ route('admin.special-days.store') }}" class="space-y-4"
                    x-data="{ closed: true }">
                    @csrf

                    <x-admin.field label="日付" name="date" for="special-date" required>
                        <x-admin.input type="date" id="special-date" name="date" required
                            value="{{ old('date', today()->toDateString()) }}" />
                    </x-admin.field>

                    <label class="flex items-center gap-2 text-sm text-stone-700">
                        <input type="hidden" name="is_closed" value="0">
                        <input type="checkbox" name="is_closed" value="1" x-model="closed"
                            class="h-4 w-4 rounded border-stone-300">
                        終日休業にする
                    </label>

                    <div class="grid grid-cols-2 gap-3" x-show="!closed">
                        <x-admin.field label="開店" name="opens_at" for="special-opens">
                            <x-admin.input type="time" id="special-opens" name="opens_at" value="{{ old('opens_at') }}" />
                        </x-admin.field>
                        <x-admin.field label="閉店" name="closes_at" for="special-closes">
                            <x-admin.input type="time" id="special-closes" name="closes_at" value="{{ old('closes_at') }}" />
                        </x-admin.field>
                    </div>

                    <x-admin.field label="備考" name="note" for="special-note" help="例：貸切のため / 設備点検のため">
                        <x-admin.input id="special-note" name="note" value="{{ old('note') }}" />
                    </x-admin.field>

                    <x-admin.button>登録する</x-admin.button>
                </form>

                @if ($specialDays->isNotEmpty())
                    <ul class="mt-6 divide-y divide-stone-100 border-t border-stone-100">
                        @foreach ($specialDays as $day)
                            <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                <div>
                                    <span class="{{ $day->date->isPast() ? 'text-stone-400' : 'text-stone-800' }}">
                                        {{ $day->date->format('Y/n/j') }}（{{ BusinessHour::DAY_LABELS[(int) $day->date->dayOfWeek] }}）
                                    </span>
                                    <span class="ml-2 text-xs text-stone-500">
                                        @if ($day->is_closed)
                                            休業
                                        @else
                                            {{ substr((string) $day->opens_at, 0, 5) }}〜{{ substr((string) $day->closes_at, 0, 5) }}
                                        @endif
                                        {{ $day->note ? '（'.$day->note.'）' : '' }}
                                    </span>
                                </div>
                                <form method="POST" action="{{ route('admin.special-days.destroy', $day) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-600 underline">削除</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.card>

            <x-admin.card title="サイトでの見え方">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($week as $row)
                            <tr>
                                <th class="py-2 text-left font-medium text-stone-600">{{ $row['label'] }}</th>
                                <td class="py-2 text-stone-700">
                                    @if ($row['closed'])
                                        <span class="text-stone-400">定休日</span>
                                    @else
                                        @foreach ($row['ranges'] as $range)
                                            <div>{{ $range['label'] ? $range['label'].' ' : '' }}{{ $range['range'] }}</div>
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-admin.card>
        </div>
    </div>
</x-layouts.admin>
