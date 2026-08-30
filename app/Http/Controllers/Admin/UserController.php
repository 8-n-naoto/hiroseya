<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * 管理画面のユーザー。
 *
 * 退職・交代のたびにアカウントを消すと、その人が書いたお知らせの
 * 記録まで追えなくなる。消すのではなく「利用停止」にできるようにしてある
 * （is_active を false にすると、次のアクセスで即ログアウトされる）。
 */
class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::orderBy('id')->get()]);
    }

    public function create(): View
    {
        return view('admin.users.edit', ['user' => new User(['role' => UserRole::Editor, 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:filter', 'max:191', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
        ], [], $this->attributes());

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLog::record('create', $user, "ユーザー「{$user->name}」を追加しました。");

        return redirect()->route('admin.users.index')->with('status', 'ユーザーを追加しました。');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:filter', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(10)->letters()->numbers()],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
        ], [], $this->attributes());

        $isSelf = $request->user()?->id === $user->id;

        // 自分自身の権限を落としたり、自分を止めたりできると、
        // 誰も設定を触れない状態を作れてしまう。
        $role = $isSelf ? $user->role : $data['role'];
        $active = $isSelf ? true : $request->boolean('is_active');

        // 最後の管理者を残す。0 人になると設定画面に誰も入れなくなる。
        if ($user->isOwner() && $role !== UserRole::Owner->value) {
            $otherOwners = User::query()->where('role', UserRole::Owner->value)
                ->where('is_active', true)->whereKeyNot($user->id)->count();

            if ($otherOwners === 0) {
                return back()->withErrors(['role' => '管理者が 0 人になるため、権限を変更できません。先に別の管理者を追加してください。']);
            }
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $role,
            'is_active' => $active,
        ]);

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        $user->save();

        ActivityLog::record('update', $user, "ユーザー「{$user->name}」を更新しました。");

        return redirect()->route('admin.users.index')->with('status', 'ユーザー情報を更新しました。');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return back()->withErrors(['user' => '自分自身は削除できません。']);
        }

        if ($user->isOwner() && User::where('role', UserRole::Owner->value)->count() <= 1) {
            return back()->withErrors(['user' => '最後の管理者は削除できません。']);
        }

        $name = $user->name;
        $user->delete();

        ActivityLog::record('delete', null, "ユーザー「{$name}」を削除しました。");

        return back()->with('status', 'ユーザーを削除しました。');
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        return [
            'name' => 'お名前',
            'email' => 'メールアドレス',
            'password' => 'パスワード',
            'role' => '権限',
        ];
    }
}
