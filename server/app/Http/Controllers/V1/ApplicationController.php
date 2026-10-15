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

use App\Exceptions\AutoUpdate\DownloadException;
use App\Exceptions\AutoUpdate\ParserException;
use App\Http\Controllers\Controller;
use App\Services\AutoUpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class ApplicationController extends Controller
{
    /**
     * @var AutoUpdateService
     */
    private $autoUpdateService;

    /**
     * ApplicationController constructor.
     *
     * @param AutoUpdateService $autoUpdateService
     */
    public function __construct(AutoUpdateService $autoUpdateService)
    {
        $this->autoUpdateService = $autoUpdateService;
    }

    public function retrieve()
    {
        $user = Auth::user();

        $response = [];

        if ($user && $user->admin) {
            try {
                if ($this->autoUpdateService->checkUpdate() && $this->autoUpdateService->newVersionAvailable()) {
                    $response['updates'] = $this->autoUpdateService->getVersionsToUpdate();
                }
            } catch (ParserException | DownloadException $e) {
                $response['updates'] = [];
                $response['message'] = 'Failed to parse updates: ' . $e->getMessage();
            }
        }

        return response()->json($response);
    }

    public function install()
    {
        if (Schema::hasTable('migrations')) {
            return response('', 401);
        }

        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true
        ]);

        return response('', 200);
    }

    public function update()
    {
        if (!$this->autoUpdateService->checkUpdate() || !$this->autoUpdateService->newVersionAvailable()) {
            return response('', 404);
        }

        // Extracting thousands of files can outlast the default limits; a half applied update leaves old files behind.
        set_time_limit(0);
        ignore_user_abort(true);

        $simulation = $this->autoUpdateService->update();

        // update() returns true on success and an error code (int) or false otherwise.
        if ($simulation !== true) {
            throw new RuntimeException('The update simulation failed (' . var_export($simulation, true) . '): '
                . json_encode($this->autoUpdateService->getSimulationResults()));
        }

        $result = $this->autoUpdateService->update(false);

        if ($result !== true) {
            throw new RuntimeException('The update failed (' . var_export($result, true) . '), see '
                . 'storage/logs/update-*.log');
        }

        try {
            return response('', 200);
        } catch (Throwable $e) {
            // Due to vendor changes, Laravel's response infrastructure may fail. Starting from the next request
            // the framework will work correctly.
            return null;
        }
    }

    public function refresh()
    {
        Artisan::call('config:clear');
        Artisan::call('migrate', [
            '--force' => true
        ]);
        Artisan::call('cleanup');
        return response('', 200);
    }
}
