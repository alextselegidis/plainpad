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

import localforage from 'localforage';
import account from './stores/account';

class Storage {
  tables = {};

  table(name) {
    if (!account.user) {
      throw new Error('Cannot access the database without a connected user account.');
    }

    // Cached per user, so a different user logging in within the same tab never sees the previous user's notes.
    const key = `${account.user.id}-${name}`;

    if (!this.tables[key]) {
      this.tables[key] = localforage.createInstance({
        name: this.databaseName(),
        storeName: name
      });
    }

    return this.tables[key]
  }

  databaseName() {
    return `Plainpad-${process.env.REACT_APP_VERSION}-${account.user.id}`;
  }

  async drop() {
    const name = this.databaseName();

    this.tables = {};

    await localforage.dropInstance({name});
  }
}

export default new Storage();
