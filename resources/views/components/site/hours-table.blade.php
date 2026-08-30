@props(['hours'])
{{--
    営業時間の表。今日の行に印を付ける。
    「今日やっているか」を探しに来た人が、表を読み下さずに済むようにする。
--}}
@php $today = (int) now()->dayOfWeek; @endphp

<table class="hours-table">
    <caption class="visually-hidden">曜日ごとの営業時間</caption>
    <tbody>
        @foreach ($hours->week() as $row)
            <tr @if ($row['day'] === $today) data-today="true" @endif>
                <th scope="row">
                    {{ $row['label'] }}
                    @if ($row['day'] === $today)
                        <span style="font-size:10px;letter-spacing:.1em;">（本日）</span>
                    @endif
                </th>
                <td @class(['is-closed' => $row['closed']])>
                    @if ($row['closed'])
                        定休日
                    @else
                        @foreach ($row['ranges'] as $range)
                            @if ($range['label'])<span style="color:var(--ink-faint);font-size:12px;">{{ $range['label'] }}</span> @endif{{ $range['range'] }}@if (! $loop->last)<br>@endif
                        @endforeach
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<p style="margin-top:12px;font-size:12.5px;color:var(--ink-faint);">
    定休日　{{ $hours->closedDaysLabel() }}
</p>
