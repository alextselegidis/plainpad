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
import UsersHttpClient from '../http/UsersHttpClient';
import application from './application';
import {translate} from '../lang';
import Swal from 'sweetalert2';

class UsersStore {
  // Page
  mode = 'list';

  // List
  filter = '';
  userList = [];

  // Modal
  id = null;
  name = '';
  email = '';
  password = '';
  passwordConfirmation = '';
  admin = false;

  add() {
    this.mode = 'add';
    this.id = null;
    this.name = '';
    this.email = '';
    this.password = '';
    this.passwordConfirmation = '';
    this.admin = false;
  }

  edit(user) {
    this.mode = 'edit';
    this.id = user.id;
    this.name = user.name;
    this.email = user.email;
    this.password = '';
    this.passwordConfirmation = '';
    this.admin = user.admin;
  }

  async list() {
    try {
      this.userList = await UsersHttpClient.list(this.filter);
    } catch (error) {
      application.error(translate('users.listFailure'));
      console.error(error);
    }
  }

  async save() {
    try {
      if (this.password && this.password !== this.passwordConfirmation) {
        application.error(translate('users.passwordsMismatch'));
        return;
      }

      const user = {
        id: this.id || undefined,
        name: this.name,
        email: this.email,
        password: this.password,
        admin: this.admin,
      };

      await UsersHttpClient[this.id ? 'update' : 'create'](user);

      application.success(translate('users.saveSuccess'));
      this.list(this.filter);
      this.mode = 'list';
    } catch (error) {
      application.error(translate('users.saveFailure'));
      console.error(error);
    }
  }

  async delete(user) {
    try {
      const result = await Swal.fire({
        text: translate('users.deletePrompt'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d94e5c',
        confirmButtonText: translate('users.delete'),
        reverseButtons: true
      });

      if (!result.value) {
        return;
      }

      await UsersHttpClient.delete(user.id);

      application.success(translate('users.deleteSuccess'));
      this.list(this.filter);
      this.mode = 'list';
    } catch (error) {
      application.error(translate('users.deleteFailure'));
      console.error(error);
    }
  }
}

decorate(UsersStore, {
  mode: observable,
  filter: observable,
  userList: observable,
  name: observable,
  email: observable,
  password: observable,
  passwordConfirmation: observable,
  admin: observable,
});

export default new UsersStore();
