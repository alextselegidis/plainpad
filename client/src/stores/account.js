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

import {decorate, observable} from 'mobx';
import {translate} from '../lang';
import application from './application';
import notes from './notes';
import SessionsHttpClient from '../http/SessionsHttpClient';
import UsersHttpClient from '../http/UsersHttpClient';

class AccountStore {
  session = null;
  user = null;
  passwordRecovered = false;

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
    localStorage.removeItem('Plainpad.Account');
    notes.reset();
    notes.empty();
    this.user = null;

    if (!this.session) {
      return;
    }

    try {
      await SessionsHttpClient.delete(this.session);
      application.success(translate('account.successfullyLoggedOut'));
    } catch (error) {
      application.error(translate('account.failedToLogOut'));
      console.error(error);
    }

    this.session = null;
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
  session: observable,
});

export default new AccountStore();
