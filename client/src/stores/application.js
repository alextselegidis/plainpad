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
import {Spinner} from 'spin.js';
import toastr from 'toastr';
import ApplicationHttpClient from '../http/ApplicationHttpClient';
import account from './account';
import notes from './notes';
import OfflineError from '../http/OfflineError';
import {translate} from '../lang';

class ApplicationStore {
  config = null;
  loadingSpinner = null;
  installation = null;
  online = true;

  loading(toggle, target = null) {
    target = target || document.body;
    const root = document.querySelector('#root');

    if (!this.loadingSpinner) {
      const opts = {
        lines: 13, // The number of lines to draw
        length: 38, // The length of each line
        width: 17, // The line thickness
        radius: 45, // The radius of the inner circle
        scale: 0.5, // Scales overall size of the spinner
        corners: 1, // Corner roundness (0..1)
        color: '#564b65', // CSS color or array of colors
        fadeColor: 'transparent', // CSS color or array of colors
        speed: 1, // Rounds per second
        rotate: 0, // The rotation offset
        animation: 'spinner-line-shrink', // The CSS animation name for the lines
        direction: 1, // 1: clockwise, -1: counterclockwise
        zIndex: 2e9, // The z-index (defaults to 2000000000)
        className: 'spinner position-fixed', // The CSS class to assign to the spinner
        top: '50%', // Top position relative to parent
        left: '50%', // Left position relative to parent
        shadow: '0 0 1px transparent', // Box-shadow for the lines
        position: 'absolute' // Element positioning
      };

      this.loadingSpinner = new Spinner(opts).spin(target);
      root.style.opacity = '0.2';

      return;
    }

    if (toggle) {
      this.loadingSpinner.spin(target);
      root.style.opacity = '0.2';
    }

    if (!toggle) {
      this.loadingSpinner.stop();
      root.style.opacity = '1';
    }
  }

  message(content, type = 'info') {
    toastr.options = {
      closeButton: false,
      debug: false,
      newestOnTop: false,
      progressBar: false,
      positionClass: 'toast-bottom-right',
      preventDuplicates: true,
      onclick: null,
      showDuration: '30000',
      hideDuration: '1000',
      timeOut: '5000',
      extendedTimeOut: '1000',
      showEasing: 'swing',
      hideEasing: 'linear',
      showMethod: 'fadeIn',
      hideMethod: 'fadeOut'
    };

    toastr[type](content);
  }

  success(content) {
    this.message(content, 'success');
  }

  info(content) {
    this.message(content, 'info');
  }

  warning(content) {
    this.message(content, 'warning');
  }

  error(content) {
    this.message(content, 'error');
  }

  async initialize() {
    this.observeNetworkStatus();
    this.suppressSidebarOverlap();

    const localAccount = JSON.parse(localStorage.getItem('Plainpad.Account'));
    account.load(localAccount);

    try {
      this.config = await ApplicationHttpClient.retrieve();
    } catch (error) {
      if (error instanceof OfflineError) {
        this.online = false;
        return;
      }

      console.error(error);
    }

    window.addEventListener('beforeinstallprompt', (event) => {
      event.preventDefault();
      this.installation = event;
      return false;
    });
  }

  install() {
    if (!this.installation) {
      return;
    }

    this.installation.prompt();
  }

  async update() {
    try {
      await ApplicationHttpClient.update();
      await ApplicationHttpClient.refresh();
      this.success(translate('application.updateSuccess'));
      setTimeout(() => window.location.reload(), 3000);
    } catch (error) {
      this.error(translate('application.updateFailure'));
      console.error(error);
    }
  }

  observeNetworkStatus() {
    this.online = navigator.onLine;
    setTimeout(() => this.observeNetworkStatus(), 1000);
  }

  suppressSidebarOverlap() {
    document.body.addEventListener('click', (event) => {
      const target = event.target;

      if (!target.classList.contains('navbar-toggler-icon')) {
        return;
      }

      if (target.parentNode.classList.contains('aside-toggler') && document.body.classList.contains('sidebar-show')) {
        document.body.classList.remove('sidebar-show');
      }

      if (!target.parentNode.classList.contains('aside-toggler') && document.body.classList.contains('aside-menu-show')) {
        document.body.classList.remove('aside-menu-show');
      }
    });
  }
}

decorate(ApplicationStore, {
  installation: observable,
  online: observable,
  config: observable
});

export default new ApplicationStore();
