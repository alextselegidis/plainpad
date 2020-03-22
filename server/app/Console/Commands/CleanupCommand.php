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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class CleanupCommand extends Command
{
    protected $signature = 'cleanup';

    protected $description = 'Removed unnecessary system files.';

    protected $toRemove = [
    ];

    public function handle()
    {
        $adapter = new Local(base_path());
        $filesystem = new Filesystem($adapter);

        foreach ($this->toRemove as $path) {
            if (!$filesystem->has($path)) {
                continue;
            }

            $result = $filesystem->delete($path);

            if (!$result) {
                Log::warning('Could not delete unnecessary path on cleanup: ' . $path);
            }
        }
    }
}
