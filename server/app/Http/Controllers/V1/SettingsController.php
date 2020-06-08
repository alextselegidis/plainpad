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
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function retrieve()
    {
        $settings = Setting::all()->mapWithKeys(function ($setting) {
            return [$setting['name'] => $setting['value']];
        });

        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $settings = $request->input();

        foreach ($settings as $name => $value) {
            Setting::where('name', $name)->update([
                'value' => (string)$value
            ]);
        }

        return response('', 200);
    }
}
