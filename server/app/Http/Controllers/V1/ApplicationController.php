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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Monolog\Handler\StreamHandler;
use VisualAppeal\AutoUpdate;
use VisualAppeal\Exceptions\DownloadException;
use VisualAppeal\Exceptions\ParserException;

class ApplicationController extends Controller
{
    public function retrieve()
    {
        $user = Auth::user();

        $response = [];

        if ($user && $user->admin) {
            $update = $this->getAutoUpdate();

            try {
                if ($update->checkUpdate() && $update->newVersionAvailable()) {
                    $response['updates'] = $update->getVersionsToUpdate();
                }
            } catch (ParserException | DownloadException $e) {
                $response['updates'] = '';
                $response['message'] = 'Failed to parse updates: ' . $e->getMessage();
            }
        }

        return response()->json($response);
    }

    public function install(Request $request)
    {
        if (Schema::hasTable('migrations')) {
            return response('', 401);
        }

        Artisan::call('migrate:fresh --seed');

        return response('', 200);
    }

    public function update()
    {
        $update = $this->getAutoUpdate();

        if (!$update->checkUpdate() || !$update->newVersionAvailable()) {
            return response('', 404);
        }

        $simulation = $update->update();

        if (!$simulation) {
            throw new \RuntimeException('The update simulation failed: ' . json_encode($update->getSimulationResults()));
        }

        $update->setOnAllUpdateFinishCallbacks(function () {
            Artisan::call('migrate');
            Artisan::call('cleanup');
        });

        $update->update(false);

        return response('', 200);
    }

    private function getAutoUpdate()
    {
        $update = new AutoUpdate(base_path('storage/updates/'), base_path(), 60);
        $update->setCurrentVersion(config('app.version'));
        $update->setUpdateUrl(env('APP_REPOSITORY'));
        $update->setSslVerifyHost(false);
        $update->addLogHandler(new StreamHandler(base_path('storage/logs/update-' . date('Y-m-d') . '.log')));
        return $update;
    }
}
