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

class SettingStore {
  defaultLocale = 'en-US';
  mailDriver = 'smtp';
  mailHost = 'smtp.example.com';
  mailPort = '2525';
  mailUsername = '';
  mailPassword = '';
  mailEncryption = 'tls';
  mailFromAddress = 'from@example.org';
  mailFromName = 'Plainpad';

  async fetch() {
    try {
      const settings = await SettingsHttpClient.retrieve();
      this.defaultLocale = settings.default_locale;
      this.mailDriver = settings.mail_driver;
      this.mailHost = settings.mail_host;
      this.mailPort = settings.mail_port;
      this.mailUsername = settings.mail_username;
      this.mailPassword = settings.mail_password;
      this.mailEncryption = settings.mail_encryption;
      this.mailFromAddress = settings.mail_from_address;
      this.mailFromName = settings.mail_from_name;
    } catch (error) {
      application.error(translate('settings.fetchFailure'));
      console.error(error);
    }
  }

  async save() {
    try {
      const settings = {
        default_locale: this.defaultLocale,
        mail_driver: this.mailDriver,
        mail_host: this.mailHost,
        mail_port: this.mailPort,
        mail_username: this.mailUsername,
        mail_password: this.mailPassword,
        mail_encryption: this.mailEncryption,
        mail_from_address: this.mailFromAddress,
        mail_from_name: this.mailFromName,
      };

      await SettingsHttpClient.update(settings);

      application.success(translate('settings.saveSuccess'));
    } catch (error) {
      application.error(translate('settings.saveFailure'));
      console.error(error);
    }
  }
}

decorate(SettingStore, {
  defaultLocale: observable,
  mailDriver: observable,
  mailHost: observable,
  mailPort: observable,
  mailUsername: observable,
  mailPassword: observable,
  mailEncryption: observable,
  mailFromAddress: observable,
  mailFromName: observable,
});

export default new SettingStore();
