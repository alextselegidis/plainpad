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

import enUS from './en-US';
import deDE from './de-DE';
import account from '../stores/account';
import {createIntl} from 'react-intl';

const messages = {
  'en-US': flatten(enUS),
  'de-DE': flatten(deDE)
};

let intl = null;

function flatten(object) {
  const result = {};

  for (let key in object) {
    if (!object.hasOwnProperty(key)) {
      continue;
    }

    if (typeof object[key] !== 'object' || object[key] === null) {
      result[key] = object[key];
      continue;
    }

    const flatObject = flatten(object[key]);

    for (let childKey in flatObject) {
      if (!flatObject.hasOwnProperty(childKey)) {
        continue;
      }

      result[key + '.' + childKey] = flatObject[childKey];
    }
  }

  return result;
}

function translate(id, values = {}) {
  if (!intl) {
    const locale = account.user ? account.user.locale : 'en-US';

    intl = createIntl({
      locale,
      messages: messages[locale]
    });
  }

  return intl.formatMessage({
    id,
    values
  });
}

export {
  messages,
  translate
}
