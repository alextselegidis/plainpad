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

class ApplicationHttpClient {
  static retrieve() {
    const method = 'GET';

    const url = '';

    return HttpClient.request(method, url, null);
  }

  static update() {
    const method = 'PUT';

    const url = '';

    return HttpClient.request(method, url, null);
  }

  static refresh() {
    const method = 'POST';

    const url = 'refresh';

    return HttpClient.request(method, url, null);
  }
}

export default ApplicationHttpClient;
