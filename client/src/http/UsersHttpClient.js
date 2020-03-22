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

import HttpClient from './HttpClient';

class UsersHttpClient {
  static list(filter, sort, direction, page, length, fields) {
    const method = 'GET';

    const queryParams = [];

    if (filter) {
      queryParams.push(`filter=${filter}`);
    }

    if (sort) {
      queryParams.push(`sort=${sort}`);
    }

    if (direction) {
      queryParams.push(`direction=${direction}`);
    }

    if (page) {
      queryParams.push(`page=${page}`);
    }

    if (length) {
      queryParams.push(`length=${length}`);
    }

    if (fields) {
      queryParams.push(`fields=${fields.join(',')}`);
    }

    const url = `users${queryParams.length ? '?' + queryParams.join('&') : ''}`;

    return HttpClient.request(method, url);
  }

  static retrieve(id) {
    const method = 'GET';

    const url = `users/${id}`;

    return HttpClient.request(method, url);
  }

  static create(user) {
    const method = 'POST';

    const url = 'users';

    return HttpClient.request(method, url, user);
  }

  static update(user) {
    const method = 'PUT';

    const url = `users/${user.id}`;

    return HttpClient.request(method, url, user);
  }

  static delete(id) {
    const method = 'DELETE';

    const url = `users/${id}`;

    return HttpClient.request(method, url);
  }

  static recoverPassword(email) {
    const method = 'POST';

    const url = 'users/recovery';

    const data = {
      email
    };

    return HttpClient.request(method, url, data);
  }
}

export default UsersHttpClient;
