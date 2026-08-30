<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * イベント。
 *
 * 開催期間で「開催中」「終了」を自動で出し分ける。
 * 手で切り替える形にすると、終わったイベントが出しっぱなしになる。
 */
class EventController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.events.index', [
            'items' => Event::query()
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
                ->orderByDesc('starts_on')
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $request->only(['status', 'q']),
        ]);
    }

    public function create(): View
    {
        return view('admin.events.edit', [
            'event' => new Event(['status' => PublishStatus::Draft, 'starts_on' => today()]),
        ]);
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        $event = new Event;
        $this->fill($event, $request);

        ActivityLog::record('create', $event, "イベント「{$event->title}」を追加しました。");

        return redirect()->route('admin.events.edit', $event)->with('status', 'イベントを保存しました。');
    }

    public function edit(Event $event): View
    {
        return view('admin.events.edit', ['event' => $event->load(['mainImage', 'seoMeta'])]);
    }

    public function update(ArticleRequest $request, Event $event): RedirectResponse
    {
        $this->fill($event, $request);

        ActivityLog::record('update', $event, "イベント「{$event->title}」を更新しました。");

        return redirect()->route('admin.events.edit', $event)->with('status', 'イベントを更新しました。');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $title = $event->title;
        $event->delete();

        ActivityLog::record('delete', $event, "イベント「{$title}」を削除しました。");

        return redirect()->route('admin.events.index')->with('status', "イベント「{$title}」を削除しました。");
    }

    private function fill(Event $event, ArticleRequest $request): void
    {
        $data = $request->validated();

        $event->fill([
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'],
            'main_media_id' => $data['main_media_id'] ?? null,
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        if (blank($event->slug) || filled($data['slug'] ?? null)) {
            $event->slug = Slug::forModel($event, (string) ($data['slug'] ?? ''), 'event-'.now()->format('YmdHis'));
        }

        $event->save();

        $title = $data['seo_title'] ?? null;
        $description = $data['seo_description'] ?? null;

        if (blank($title) && blank($description)) {
            $event->seoMeta()->delete();
        } else {
            $event->seoMeta()->updateOrCreate([], ['title' => $title, 'description' => $description]);
        }
    }
}
