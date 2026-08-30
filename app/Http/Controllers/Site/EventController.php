<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\Seo;
use App\Support\SiteContext;
use App\Support\StructuredData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Seo $seo): View
    {
        $seo->page('events')
            ->title('イベント')
            ->breadcrumbs([['イベント', route('events.index')]]);

        return view('site.events.index', [
            // 開催中を先に、終了したものを後に並べる。
            'ongoing' => Event::listable()->ongoing()
                ->orderBy('sort_order')->orderBy('starts_on')->with('mainImage')->get(),
            'finished' => Event::listable()->finished()
                ->orderByDesc('ends_on')->with('mainImage')->limit(12)->get(),
        ]);
    }

    public function show(
        Request $request,
        string $slug,
        Seo $seo,
        SiteContext $site,
        StructuredData $schema,
    ): View {
        $event = Event::query()->where('slug', $slug)->with(['mainImage', 'seoMeta'])->firstOrFail();

        abort_unless($event->isPublished() || $request->user(), 404);

        $seo->model($event)
            ->breadcrumbs([
                ['イベント', route('events.index')],
                [$event->title, route('events.show', $event->slug)],
            ])
            ->type('article')
            ->schema($schema->event($event, $site->store()));

        if (! $event->isPublished()) {
            $seo->noindex();
        }

        return view('site.events.show', [
            'event' => $event,
            'isPreview' => ! $event->isPublished(),
        ]);
    }
}
