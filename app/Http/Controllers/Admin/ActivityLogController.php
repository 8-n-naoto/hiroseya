<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 操作ログ。
 *
 * 複数人で運用すると「いつの間にか値が変わっている」が必ず起きる。
 * 誰が何をしたかを残しておくと、原因を探す時間がなくなる。
 */
class ActivityLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('admin.activity.index', [
            'logs' => ActivityLog::query()
                ->with('user')
                ->when($request->filled('user'), fn ($q) => $q->where('user_id', $request->integer('user')))
                ->orderByDesc('id')
                ->paginate(50)
                ->withQueryString(),
            'users' => \App\Models\User::orderBy('id')->pluck('name', 'id'),
            'filters' => $request->only('user'),
        ]);
    }
}
