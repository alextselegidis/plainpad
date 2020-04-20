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

import account from '../stores/account';
import OfflineError from './OfflineError';

class HttpClient {
  static baseUrl(segment) {
    return process.env.REACT_APP_BASE_URL + '/v1/' + segment;
  }

  static prepare(method, url, data) {
    const input = this.baseUrl(url);

    const headers = {
      'Content-Type': 'application/json'
    };

    if (account.session) {
      headers.Authorization = `Bearer ${account.session.token}`;
    }

    const init = {
      method,
      headers,
      mode: 'cors',
      credentials: 'same-origin',
      redirect: 'follow',
      referrer: 'no-referrer',
      body: data ? JSON.stringify(data) : undefined
    };

    return new Request(input, init);
  }

  static request(method, url, data) {
    if (!navigator.onLine) {
      return new Promise((resolve, reject) => {
        reject(new OfflineError('The application is operating in offline mode.'));
      });
    }

    return new Promise((resolve, reject) => {
      const request = this.prepare(method, url, data);

      fetch(request)
        .then((response) => {
          if (!response.ok) {
            this.throwRequestFailed(response);
          }

          return response;
        })
        .then((response) => {
          return response.text();
        })
        .then((response) => {
          return response.length ? JSON.parse(response) : {};
        })
        .then((json) => {
          resolve(json);
        })
        .catch((error) => {
          reject(error.message);
          console.error(error.message);
        });
    });
  }

  static throwRequestFailed(response) {
    if (response.status === 401 && account && !account.session) {
      // The login request failed.
      return;
    }

    if (response.status === 401 && account && account.session) {
      // The session has expired, the user has to log in again.
      localStorage.removeItem('Plainpad.Account');
      window.location.reload();
      return;
    }

    const error = new Error();
    error.code = response.status;

    if (!response.bodyUsed) {
      error.message = response.statusText;
      throw error;
    } else {
      response
        .text()
        .then((message) => {
          error.message = message;
          throw error;
        });
    }
  }
}

export default HttpClient;
