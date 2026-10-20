<?php

/*
  Plainpad - Self Hosted Note Taking App

  Copyright (C) Alex Tselegidis - https://alextselegidis.com

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <https://www.gnu.org/licenses/>
*/

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Mail\PasswordRecovered;
use App\Models\Note;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

use function Illuminate\Support\defer;

class UsersController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users|max:255',
            'password' => 'required|string|min:8',
            'view' => 'string',
            'line' => 'string',
            'sort' => 'string',
            'theme' => 'string',
            'encrypt' => 'boolean',
            'admin' => 'required|boolean',
        ]);

        $user = new User;
        $user->id = (string) Str::uuid();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->locale = $request->input('locale') ?? 'en-US';
        $user->view = $request->input('view') ?? 'comfortable';
        $user->line = $request->input('line') ?? 'full';
        $user->sort = $request->input('sort') ?? 'modified';
        $user->theme = $request->input('theme') ?? 'light';
        $user->encrypt = $request->input('encrypt') ?? false;
        $user->admin = $request->input('admin');
        $user->save();

        return response()->json($user, 201);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id,
            'password' => 'string|nullable|min:8',
            'current_password' => 'string|nullable',
            'locale' => 'string',
            'view' => 'string',
            'line' => 'string',
            'sort' => 'string',
            'theme' => 'string',
            'encrypt' => 'boolean',
            'admin' => 'boolean',
        ]);

        $user = User::find($id);

        if (! $user) {
            return response('', 401);
        }

        $authUser = Auth::user();

        if (! $authUser->admin && $authUser->id !== $user->id) {
            return response('', 401);
        }

        // Changing your own email or password needs the current password, so a stolen token cannot take the account over.
        // Admins editing another user are exempt.
        $credentialsChanged = $request->input('password') || $request->input('email') !== $user->email;

        if ($authUser->id === $user->id && $credentialsChanged
            && ! Hash::check((string) $request->input('current_password'), $user->password)) {
            // 422 and not 401, the client treats a 401 as an expired session and logs out.
            return response()->json(['message' => 'The current password is incorrect.'], 422);
        }

        // Missing fields keep their current value, the admin users screen does not send "encrypt".
        $encrypt = $request->boolean('encrypt', $user->encrypt);

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = $request->input('password') ? Hash::make($request->input('password')) : $user->password;
        $user->locale = $request->input('locale') ?? $user->locale;
        $user->view = $request->input('view') ?? $user->view;
        $user->line = $request->input('line') ?? $user->line;
        $user->sort = $request->input('sort') ?? $user->sort;
        $user->theme = $request->input('theme') ?? $user->theme;
        $user->encrypt = $encrypt;

        if ($authUser->admin && $request->has('admin')) {
            $user->admin = $request->boolean('admin');
        }

        // One transaction, so a failure halfway through the notes does not leave them partly encrypted. The admin rows
        // are locked, so two admins demoting each other at the same time cannot both pass the last admin check.
        $saved = DB::transaction(function () use ($user, $encrypt) {
            if ($user->getOriginal('admin') && ! $user->admin
                && User::where('admin', true)->lockForUpdate()->count() === 1) {
                return false;
            }

            Note::toggleEncryption($user, (bool) $user->getOriginal('encrypt'), $encrypt);
            $user->save();

            return true;
        });

        if (! $saved) {
            return response()->json(['message' => 'The last admin cannot be demoted.'], 422);
        }

        if ($request->input('password')) {
            // Revoke every other session so a stolen token does not survive the password change.
            Session::where('user_id', $user->id)->where('token', '!=', $request->bearerToken())->delete();
        }

        if ($authUser->id === $user->id) {
            $authUser->encrypt = $user->encrypt;
        }

        return response()->json($user, 200);
    }

    public function delete(Request $request, string $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response('', 404);
        }

        if ($user->id === Auth::user()->id) {
            return response('', 400); // Cannot delete themselves.
        }

        // Locked, so two admins deleting each other at the same time cannot remove every admin.
        $deleted = DB::transaction(function () use ($user) {
            if ($user->admin && User::where('admin', true)->lockForUpdate()->count() === 1) {
                return false;
            }

            return $user->delete();
        });

        if (! $deleted) {
            return response()->json(['message' => 'The last admin cannot be deleted.'], 422);
        }

        return response('', 200);
    }

    public function list(Request $request)
    {
        $defaultFields = [
            'id',
            'name',
            'email',
            'admin',
            'password',
            'locale',
            'view',
            'line',
            'sort',
            'theme',
            'encrypt',
            'created_at',
            'updated_at',
        ];

        $filter = '%'.$request->input('filter').'%';
        $sort = in_array($request->input('sort'), $defaultFields, true) ? $request->input('sort') : 'name';
        $direction = strtolower($request->input('direction')) === 'desc' ? 'desc' : 'asc';
        $page = $request->input('page') ?? 1;
        $length = $request->input('length') ?? 1000;
        $fields = $request->input('fields') ? array_intersect($defaultFields, explode(',', $request->input('fields'))) : $defaultFields;

        $users = User::where('name', 'like', $filter)
            ->orWhere('email', 'like', $filter)
            ->select(...$fields)
            ->orderBy($sort, $direction)
            ->limit($length)
            ->offset(($page - 1) * $length)
            ->get();

        return response()->json($users);
    }

    public function retrieve(Request $request, string $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response('', 404);
        }

        $authUser = Auth::user();

        if (! $authUser->admin && $authUser->id !== $user->id) {
            return response('', 401);
        }

        return response()->json($user);
    }

    public function recoverPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user) {
            App::setLocale($user->locale);

            $token = Str::random(64);

            // Replace any previous token, a new request must invalidate older reset links.
            DB::transaction(function () use ($user, $token) {
                DB::table('password_resets')->where('email', $user->email)->delete();
                DB::table('password_resets')->insert(
                    ['email' => $user->email, 'token' => Hash::make($token), 'created_at' => now()]
                );
            });

            $resetUrl = config('app.url').'/#/reset-password?email='.urlencode($user->email).'&token='.urlencode($token);

            // Sent after the response, so neither a mail error (500) nor the send time reveals that the email exists.
            defer(function () use ($user, $resetUrl) {
                try {
                    Mail::to($user)->send(new PasswordRecovered($resetUrl));
                } catch (Throwable $e) {
                    report($e);
                }
            });
        }

        // Always return 200 regardless of whether the email exists, to prevent account enumeration.
        return response('', 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        $record = DB::table('password_resets')
            ->where('email', $request->input('email'))
            ->orderByDesc('created_at')
            ->first();

        if (! $record || ! Hash::check($request->input('token'), $record->token)) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        $expireMinutes = config('auth.passwords.users.expire', 60);

        if (Carbon::parse($record->created_at)->lt(now()->subMinutes($expireMinutes))) {
            DB::table('password_resets')->where('email', $request->input('email'))->delete();

            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        Session::where('user_id', $user->id)->delete();

        DB::table('password_resets')->where('email', $request->input('email'))->delete();

        return response('', 200);
    }
}
