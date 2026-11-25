<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerAccountController extends Controller
{
    public function registerForm()
    {
        return view('helpdesk.account', ['mode' => 'register']);
    }

    public function register(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:12|max:128|confirmed']);
        User::create(array_merge($data, ['role' => 'user', 'permissions' => User::defaultPermissions('user'), 'is_active' => true]));

        return redirect()->route('login')->with('status', 'Your customer account is ready. Sign in to open and track tickets.');
    }

    public function forgotForm()
    {
        return view('helpdesk.account', ['mode' => 'forgot']);
    }

    public function forgot(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $request->validate(['email' => 'required|email|max:255']);
        $user = User::where('email', $request->input('email'))->where('is_active', true)->first();
        if ($user) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('status', 'If an active account exists, a password reset email has been queued.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('helpdesk.account', ['mode' => 'reset', 'token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate(['token' => 'required|string', 'email' => 'required|email', 'password' => 'required|string|min:12|max:128|confirmed']);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            if (! $user->is_active) {
                throw ValidationException::withMessages(['email' => 'This account is inactive. Contact an administrator.']);
            }
            DB::transaction(function () use ($user, $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();
            });
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect()->route('login')->with('status','Password updated. Sign in with your new password.');
    }
}
