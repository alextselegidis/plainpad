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

import {decorate, observable} from 'mobx';
import {translate} from '../lang';
import application from './application';
import notes from './notes';
import SessionsHttpClient from '../http/SessionsHttpClient';
import UsersHttpClient from '../http/UsersHttpClient';
import storage from '../storage';

class AccountStore {
  session = null;
  user = null;
  passwordRecovered = false;
  passwordReset = false;

  load(account) {
    if (!account) {
      return;
    }

    this.user = account.user;
    this.session = account.session;

    this.observeSessionExpiration();

    if (this.user) {
      notes.list();
      notes.sync();
    }
  }

  async login(email, password) {
    try {
      const session = await SessionsHttpClient.create(email, password);

      this.session = session;
      this.observeSessionExpiration();

      const user = await UsersHttpClient.retrieve(session.user_id);

      this.user = user;

      const account = {
        session,
        user
      };

      localStorage.setItem('Plainpad.Account', JSON.stringify(account));

      application.success(translate('account.successfullyLoggedIn'));

      await notes.list();
      await notes.sync();
    } catch (error) {
      application.error(translate('account.failedToLogIn'));
      console.error(error);
    }
  }

  async logout() {
    if (this.user) {
      await this.removeLocalNotes();
    }

    // Revoke the session before clearing the user, clearing it already shows the login page.
    if (this.session) {
      try {
        await SessionsHttpClient.delete(this.session);
        application.success(translate('account.successfullyLoggedOut'));
      } catch (error) {
        application.error(translate('account.failedToLogOut'));
        console.error(error);
      }
    }

    localStorage.removeItem('Plainpad.Account');
    notes.reset();
    notes.empty();
    this.user = null;
    this.session = null;
  }

  async removeLocalNotes() {
    // Upload pending changes first, notes that still cannot be synced (e.g. offline) stay on the device so that they
    // are not lost and get uploaded on the next login.
    try {
      // The first call waits for a sync that may already be running (it would write into the database after the drop),
      // the second one uploads the latest changes.
      await notes.sync();
      await notes.sync();
      clearTimeout(notes.syncTimeout);

      if (await storage.table('sync').length() === 0) {
        await storage.drop();
      }
    } catch (error) {
      console.error(error);
    }
  }

  async recoverPassword(email) {
    try {
      await UsersHttpClient.recoverPassword(email);
      this.passwordRecovered = true;
      application.success(translate('account.successfullyRecoveredPassword'));
    } catch (error) {
      application.error(translate('account.failedToRecoverPassword'));
      console.error(error);
    }
  }

  async resetPassword(email, token, password, confirm) {
    if (password !== confirm) {
      application.error(translate('account.passwordsDoNotMatch'));
      return;
    }

    try {
      await UsersHttpClient.resetPassword(email, token, password);
      this.passwordReset = true;
      application.success(translate('account.successfullyResetPassword'));
    } catch (error) {
      application.error(translate('account.failedToResetPassword'));
      console.error(error);
    }
  }

  observeSessionExpiration() {
    if (!this.session || !navigator.onLine) {
      return;
    }

    const expires = new Date(this.session.expires_at);
    const current = new Date();

    if (current > expires) {
      application.warning(translate('account.sessionExpired'));
      this.logout();
      return;
    }

    setTimeout(() => this.observeSessionExpiration(), 60000);
  }
}

decorate(AccountStore, {
  user: observable,
  passwordRecovered: observable,
  passwordReset: observable,
  session: observable,
});

export default new AccountStore();
