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
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UsersController extends Controller
{
    public function create(Request $request)
    {
        $request->validate( [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users|max:255',
            'password' => 'required|string',
            'view' => 'string',
            'line' => 'string',
            'sort' => 'string',
            'theme' => 'string',
            'encrypt' => 'boolean',
            'admin' => 'required|boolean',
        ]);

        $user = new User;
        $user->id = (string)Str::uuid();
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
        $request->validate( [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'string|nullable',
            'locale' => 'string',
            'view' => 'string',
            'line' => 'string',
            'sort' => 'string',
            'theme' => 'string',
            'encrypt' => 'boolean',
            'admin' => 'boolean',
        ]);

        $user = User::find($id);

        if (!$user) {
            return response('', 401);
        }

        $authUser = Auth::user();

        if (!$authUser->admin && $authUser->id !== $user->id) {
            return response('', 401);
        }

        Note::toggleEncryption($user, $user->encrypt, (bool)$request->input('encrypt'));

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = $request->input('password') ? Hash::make($request->input('password')) : $user->password;
        $user->locale = $request->input('locale') ?? $user->locale;
        $user->view = $request->input('view') ?? $user->view;
        $user->line = $request->input('line') ?? $user->line;
        $user->sort = $request->input('sort') ?? $user->sort;
        $user->theme = $request->input('theme') ?? $user->theme;
        $user->encrypt = $request->input('encrypt') ?? $user->encrypt;

        if ($authUser->admin) {
            $user->admin = (bool)$request->input('admin');
        }

        $user->save();

        Auth::user()->encrypt = $user->encrypt;


        return response()->json($user, 200);
    }

    public function delete(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response('', 404);
        }

        if ($user->id === Auth::user()->id) {
            return response('', 400); // Cannot delete themselves.
        }

        $user->delete();

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
            'updated_at'
        ];

        $filter = '%' . $request->input('filter') . '%';
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

        if (!$user) {
            return response('', 404);
        }

        $authUser = Auth::user();

        if (!$authUser->admin && $authUser->id !== $user->id) {
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

            DB::table('password_resets')->upsert(
                ['email' => $user->email, 'token' => Hash::make($token), 'created_at' => now()],
                ['email'],
                ['token', 'created_at']
            );

            $resetUrl = config('app.url') . '/#/reset-password?email=' . urlencode($user->email) . '&token=' . urlencode($token);

            Mail::to($user)->send(new PasswordRecovered($resetUrl));
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
            ->first();

        if (!$record || !Hash::check($request->input('token'), $record->token)) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        $expireMinutes = config('auth.passwords.users.expire', 60);

        if (Carbon::parse($record->created_at)->lt(now()->subMinutes($expireMinutes))) {
            DB::table('password_resets')->where('email', $request->input('email'))->delete();
            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        DB::table('password_resets')->where('email', $request->input('email'))->delete();

        return response('', 200);
    }
}
