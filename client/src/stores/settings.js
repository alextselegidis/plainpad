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
import SettingsHttpClient from '../http/SettingsHttpClient';
import application from './application';
import {translate} from '../lang';

class SettingsStore {
  encoded = null;

  async fetch() {
    try {
      this.encoded = await SettingsHttpClient.retrieve();
    } catch (error) {
      application.error(translate('settings.fetchFailure'));
      console.error(error);
    }
  }

  async save(settings) {
    try {
      this.encoded = {
        default_locale: settings.defaultLocale,
        mail_driver: settings.mailDriver,
        mail_host: settings.mailHost,
        mail_port: settings.mailPort,
        mail_username: settings.mailUsername,
        mail_password: settings.mailPassword,
        mail_encryption: settings.mailEncryption,
        mail_from_address: settings.mailFromAddress,
        mail_from_name: settings.mailFromName,
      };

      await SettingsHttpClient.update(this.encoded);

      application.success(translate('settings.saveSuccess'));
    } catch (error) {
      application.error(translate('settings.saveFailure'));
      console.error(error);
    }
  }
}

decorate(SettingsStore, {
  encoded: observable,
});

export default new SettingsStore();
