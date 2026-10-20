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
import account from './account';
import application from './application';
import {translate} from '../lang';
import UsersHttpClient from '../http/UsersHttpClient';
import OfflineError from '../http/OfflineError';
import notes from './notes';
import storage from '../storage';
import Swal from 'sweetalert2';

class ProfileStore {
  name = '';
  email = '';
  password = '';
  passwordConfirmation = '';
  currentPassword = '';
  locale = 'en-US';
  view = 'compact';
  line = 'full';
  sort = 'modified';
  theme = 'light';
  encrypt = false;

  load() {
    const {
      user
    } = account;

    if (!user) {
      return;
    }

    this.name = user.name;
    this.email = user.email;
    this.password = '';
    this.passwordConfirmation = '';
    this.currentPassword = '';
    this.locale = user.locale;
    this.view = user.view;
    this.line = user.line;
    this.sort = user.sort;
    this.theme = user.theme;
    this.encrypt = user.encrypt;
  }

  async save() {
    if (this.password && this.password !== this.passwordConfirmation) {
      application.error(translate('profile.passwordsMismatch'));
      return;
    }

    const credentialsChanged = !!this.password || this.email !== account.user.email;

    if (credentialsChanged && !this.currentPassword) {
      application.error(translate('profile.currentPasswordRequired'));
      return;
    }

    // The password is sent once and never kept in the account, which is stored in localStorage and re-sent on every
    // later profile update (e.g. theme toggle).
    const profile = {
      name: this.name,
      email: this.email,
      locale: this.locale,
      view: this.view,
      line: this.line,
      sort: this.sort,
      theme: this.theme,
      encrypt: this.encrypt,
    };

    const reload = this.locale !== account.user.locale;

    if (credentialsChanged) {
      // Email and password changes need the server to confirm the current password before they apply locally.
      try {
        await UsersHttpClient.update({
          ...account.user,
          ...profile,
          password: this.password,
          current_password: this.currentPassword,
        });
      } catch (error) {
        application.error(translate('profile.saveFailure'));
        console.error(error);
        return;
      }

      this.password = '';
      this.passwordConfirmation = '';
      this.currentPassword = '';
    }

    account.user = {...account.user, ...profile};

    this.updateLocalAccount();

    application.success(translate('profile.saveSuccess'));

    if (!credentialsChanged) {
      await this.saveAccountUser();
    }

    await notes.list();

    if (reload) {
      window.location.reload();
    }
  }

  async toggleTheme() {
    account.user.theme = account.user.theme === 'light' ? 'dark' : 'light';

    this.theme = account.user.theme;

    this.updateLocalAccount();

    await this.saveAccountUser();
  }

  async invalidateCache() {
    if (!navigator.onLine) {
      return;
    }

    const result = await Swal.fire({
      text: translate('profile.invalidateCachePrompt'),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d94e5c',
      confirmButtonText: translate('profile.invalidateCache'),
      reverseButtons: true
    });

    if (!result.value) {
      return;
    }

    await storage.table('notes').clear();
    await storage.table('sync').clear();

    application.success(translate('account.invalidateSuccess'));

    await notes.list();
  }

  updateLocalAccount() {
    // Older versions stored the plain password in the account, drop it.
    const {password, ...user} = account.user;

    const localAccount = {
      session: account.session,
      user
    };

    localStorage.setItem('Plainpad.Account', JSON.stringify(localAccount));
  }

  async saveAccountUser() {
    try {
      const {password, ...user} = account.user;

      await UsersHttpClient.update(user);
    } catch (error) {
      if (error instanceof OfflineError) {
        return;
      }

      application.error(translate('profile.saveFailure'));
      console.error(error);
    }
  }
}

decorate(ProfileStore, {
  name: observable,
  email: observable,
  password: observable,
  currentPassword: observable,
  passwordConfirmation: observable,
  locale: observable,
  view: observable,
  line: observable,
  sort: observable,
  theme: observable,
  encrypt: observable,
});

export default new ProfileStore();
