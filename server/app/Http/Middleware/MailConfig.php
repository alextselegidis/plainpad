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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Mail\MailQueue;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Support\Facades\Config;

class MailConfig
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $config = require base_path('config/mail.php');
        Config::set('mail', $config);
        $app = app();
        $app->register(MailServiceProvider::class);
        $app->configure('mail');
        $app->alias('mailer', Mailer::class);
        $app->alias('mailer', MailerContract::class);
        $app->alias('mailer', MailQueue::class);

        return $next($request);
    }
}
