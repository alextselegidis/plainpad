<?php

/*
  Plainpad - Self Hosted Note Taking App

  Copyright (C) 2020 Alex Tselegidis - https://alextselegidis.com

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
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SessionsController extends Controller
{
    public function create(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|string',
            'password' => 'required|string',
        ]);

        $email = $request->input('email');

        $password = $request->input('password');

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response('', 401);
        }

        $session = new Session;
        $session->id = (string)Str::uuid();
        $session->expires_at = (new \DateTime('+' . config('session.lifetime') . ' minutes'))->format('Y-m-d H:i:s');
        $session->token = Str::random(60);
        $session->user_id = $user->id;
        $session->save();

        return response()->json([
            'user_id' => $session->user_id,
            'expires_at' => $session->expires_at,
            'token' => $session->token,
        ], 201);
    }

    public function delete(Request $request, string $token)
    {
        $session = Session::where('token', $token)->first();

        if (!$session) {
            return response('', 404);
        }

        $session->delete();

        return response('', 200);
    }
}
