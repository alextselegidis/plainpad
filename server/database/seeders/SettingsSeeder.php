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

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->insert([
            'id' => (string)Str::uuid(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'default_locale',
            'value' => 'en-US'
        ]);

        DB::table('settings')->insert([
            'id' => (string)Str::uuid(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'mail_driver',
            'value' => 'smtp'
        ]);

        DB::table('settings')->insert([
            'id' => (string)Str::uuid(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'mail_host',
            'value' => 'smtp.mailtrap.io'
        ]);

        DB::table('settings')->insert([
            'id' => (string)Str::uuid(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'mail_port',
            'value' => '2525'
        ]);

        DB::table('settings')->insert([
            'id' => (string)Str::uuid(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'mail_username',
            'value' => ''
        ]);

        DB::table('settings')->insert([
            'id' => (string)Str::uuid(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'mail_password',
            'value' => ''
        ]);

        DB::table('settings')->insert([
            'id' => (string)Str::uuid(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'mail_encryption',
            'value' => 'tls'
        ]);

        DB::table('settings')->insert([
            'id' => (string)Str::uuid(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'mail_from_address',
            'value' => 'hello@example.org'
        ]);

        DB::table('settings')->insert([
            'id' => (string)Str::uuid(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'mail_from_name',
            'value' => 'Plainpad'
        ]);
    }
}
