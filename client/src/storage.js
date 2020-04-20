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

import localforage from 'localforage';
import account from './stores/account';

class Storage {
  tables = {};

  table(name) {
    if (!account.user) {
      throw new Error('Cannot access the database without a connected user account.');
    }

    if (!this.tables[name]) {
      this.tables[name] = localforage.createInstance({
        name: `Plainpad-${process.env.REACT_APP_VERSION}-${account.user.id}`,
        storeName: name
      });
    }

    return this.tables[name]
  }
}

export default new Storage();
