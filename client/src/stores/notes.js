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
import {v4 as uuidv4} from 'uuid';
import NotesHttpClient from '../http/NotesHttpClient';
import application from './application';
import account from './account';
import {translate} from '../lang';
import Swal from 'sweetalert2'
import moment from 'moment';
import storage from '../storage';
import OfflineError from '../http/OfflineError';

class NotesStore {
  saving = false;
  timeout = null;
  filter = '';
  noteList = [];
  id = null;
  title = '';
  content = '';
  pinned = false;
  createdAt = null;
  updatedAt = null;
  syncErrors = 0;

  async list() {
    const cachedNotes = [];

    await storage.table('notes').iterate((value) => {
      cachedNotes.push(value);
    });

    const filteredNotes = this.filterNotes(cachedNotes);

    this.noteList = this.sortNotes(filteredNotes);
  }

  add() {
    this.saving = true;
    this.id = null;
    this.title = this.filter || 'New note ...';
    this.content = this.title;
    this.pinned = false;
    this.createdAt = null;
    this.updatedAt = null;
    this.save();
  }

  async select(id) {
    const textarea = document.querySelector('.note-content');
    const selectionStart = textarea ? textarea.selectionStart : 0;
    const selectionEnd = textarea ? textarea.selectionEnd : 0;
    const isSameNote = this.id === id;

    window.location.href = `#/notes/${id}`;

    const localNote = await storage.table('notes').getItem(id);

    if (localNote) {
      this.id = localNote.id;
      this.title = localNote.title;
      this.content = localNote.content;
      this.pinned = localNote.pinned;
      this.createdAt = localNote.created_at;
      this.updatedAt = localNote.updated_at;
    }

    try {
      const serverNote = await NotesHttpClient.retrieve(id);

      const serverChanged = moment(serverNote.updated_at);
      const localChanged = moment(localNote.updated_at);

      if (serverChanged.isAfter(localChanged)) {
        storage.table('notes').setItem(id, serverNote);
        this.title = serverNote.title;
        this.content = serverNote.content;
        this.pinned = serverNote.pinned;
        this.createdAt = serverNote.created_at;
        this.updatedAt = serverNote.updated_at;
      }
    } catch (error) {
      if (!(error instanceof OfflineError)) {
        application.error(translate('notes.fetchFailure'));
        console.error(error);
      }
    }

    if (textarea) {
      if (isSameNote) {
        textarea.selectionStart = selectionStart;
        textarea.selectionEnd = selectionEnd;
      } else {
        textarea.selectionStart = 0;
        textarea.selectionEnd = 0;
        window.scrollTo(0, 0);
      }

      textarea.focus();
    }
  }

  async save() {
    this.saving = true;

    const isNewNote = this.id === null;

    let id = isNewNote ? `local-${uuidv4()}` : this.id;

    const isLocalNote = id.includes('local');

    const note = {
      id,
      user_id: account.user.id,
      created_at: this.createdAt || moment().format('YYYY-MM-DD HH:mm:ss'),
      updated_at: moment().format('YYYY-MM-DD HH:mm:ss'),
      title: this.title,
      content: this.content,
      pinned: this.pinned
    };

    if (!navigator.onLine) {
      await storage.table('sync').setItem(id, {
        date: new Date(),
        type: isLocalNote ? 'create' : 'update'
      });
    }

    const cache = await storage.table('notes').getItem(id);
    await storage.table('notes').setItem(id, {...cache, ...note});

    try {
      const response = await NotesHttpClient[isNewNote ? 'create' : 'update'](note);

      if (isNewNote) {
        storage.table('notes').removeItem(id);
      }

      id = response.id;

      await storage.table('notes').setItem(response.id, response);
    } catch (error) {
      if (!(error instanceof OfflineError)) {
        application.error(translate('notes.saveFailure'));
        console.error(error);
      }
    }

    this.list();

    if (isNewNote) {
      await this.select(id);
    }

    this.saving = false;
  }

  delete() {
    Swal.fire({
      text: translate('notes.deletePrompt'),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d94e5c',
      confirmButtonText: translate('notes.delete'),
      reverseButtons: true
    }).then(async (result) => {
      if (!result.value) {
        return;
      }

      await storage.table('notes').removeItem(this.id);
      application.success(translate('notes.deleteSuccess'));

      window.location.href = `#/notes`;
      document.querySelector('.aside-toggler').click(); // Close the sidebar.

      if (!navigator.onLine) {
        await storage.table('sync').setItem(this.id, {
          date: moment().toDate(),
          type: 'delete'
        });

        this.list();

        return;
      }

      try {
        await NotesHttpClient.delete(this.id);
      } catch (error) {
        if (!(error instanceof OfflineError)) {
          application.error(translate('notes.deleteFailure'));
          console.error(error);
        }
      }

      this.list();
      this.reset();
    });
  }

  download() {
    const filename = `${this.title}.txt`;
    const blob = new Blob([this.content], {type: 'text/plain'});

    if (window.navigator.msSaveOrOpenBlob) {
      window.navigator.msSaveBlob(blob, filename);
      return;
    }

    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
  }

  async pin() {
    try {
      this.pinned = true;

      const cache = await storage.table('notes').getItem(this.id);
      cache.pinned = true;
      await storage.table('notes').setItem(this.id, cache);

      await NotesHttpClient.pinned(this.id, this.pinned);
      await this.list();
    } catch (error) {
      if (!(error instanceof OfflineError)) {
        application.error(translate('notes.pinFailure'));
        console.error(error);
      }
    }
  }

  async unpin() {
    try {
      this.pinned = false;

      const cache = await storage.table('notes').getItem(this.id);
      cache.pinned = false;
      await storage.table('notes').setItem(this.id, cache);

      await NotesHttpClient.pinned(this.id, this.pinned);
      this.list();
    } catch (error) {
      if (!(error instanceof OfflineError)) {
        application.error(translate('notes.unpinFailure'));
        console.error(error);
      }
    }
  }

  updateContent(content) {
    this.lockPage();

    this.content = content;

    if (content.includes("\n")) {
      const title = content.match(/^(.*)\n/)[1];
      this.title = title.substring(0, 40).trim();
    } else {
      this.title = content.substring(0, 40).trim();
    }

    if (!this.title) {
      this.title = 'NO-TITLE';
    }

    if (this.timeout) {
      clearTimeout(this.timeout);
    }

    this.timeout = setTimeout(() => {
      this.save();
      this.unlockPage();
    }, 1000);
  }

  updateFilter(filter) {
    this.filter = filter;

    if (this.timeout) {
      clearTimeout(this.timeout);
    }

    this.timeout = setTimeout(() => this.list(), 1000);
  }

  lockPage() {
    window.onbeforeunload = function () {
      return true;
    };
  }

  unlockPage() {
    window.onbeforeunload = null;
  }

  reset() {
    this.id = null;
    this.title = '';
    this.content = '';
    this.pinned = '';
    this.createdAt = null;
    this.updatedAt = null;
  }

  async share() {
    if (!navigator.canShare) {
      return;
    }

    const filename = `${this.title}.txt`;
    const blob = new Blob([this.content], {type: 'text/plain'});
    const file = new File([blob], filename, {type: blob.type});

    try {
      await navigator.share({
        title: this.title,
        files: [file],
      });

      application.success(translate('notes.shareSuccess'));

    } catch (error) {
      if (!(error instanceof OfflineError)) {
        application.error(translate('notes.shareFailure'));
        console.error(error);
      }
    }
  }

  filterNotes(notes) {
    return notes.filter((note) => {
      const regex = new RegExp(this.filter, 'i');

      return note.title.match(regex)
        || note.content.match(regex);
    });
  }

  sortNotes(notes) {
    let sortedNotes;

    switch (account.user.sort) {
      default:
      case 'modified':
        sortedNotes = notes.sort((a, b) => {
          const aModified = (new Date(a.updated_at)).getTime();
          const bModified = (new Date(b.updated_at)).getTime();

          if (aModified > bModified) {
            return -1;
          }

          if (aModified < bModified) {
            return 1;
          }

          return 0;
        });
        break;

      case 'created':
        sortedNotes = notes.sort((a, b) => {
          const aCreated = (new Date(a.created_at)).getTime();
          const bCreated = (new Date(b.created_at)).getTime();

          if (aCreated < bCreated) {
            return 1;
          }

          if (aCreated > bCreated) {
            return -1;
          }

          return 0;
        });
        break;

      case 'alphabetical':
        sortedNotes = notes.sort((a, b) => {
          const aTitle = a.title;
          const bTitle = b.title;

          if (aTitle < bTitle) {
            return -1;
          }

          if (aTitle > bTitle) {
            return 1;
          }

          return 0;
        });
        break;
    }

    return sortedNotes.sort((a, b) => {
      if (a.pinned && !b.pinned) {
        return -1;
      }

      if (!a.pinned && b.pinned) {
        return 1;
      }

      return 0;
    });
  }

  async sync() {
    if (!navigator.onLine || !account.user) {
      return;
    }

    if (this.syncErrors >= 10) {
      application.warning(translate('notes.syncingGotDisabled'));
      return;
    }

    const requests = [];

    try {
      const localNotes = {};

      await storage.table('notes').iterate((localNote, id) => {
        localNotes[id] = localNote;
      });

      const localChanges = {};

      await storage.table('sync').iterate((localChange, id) => {
        localChanges[id] = localChange;
      });

      const serverNotes = await NotesHttpClient.list(null, null, null, null, null, [
        'id',
        'created_at',
        'updated_at',
        'pinned',
        'title',
        'user_id'
      ]);

      for (let serverNote of serverNotes) {
        const localNote = localNotes[serverNote.id];
        const localChange = localChanges[serverNote.id];

        if (!localNote && !localChange) {
          const serverNoteWithContent = await NotesHttpClient.retrieve(serverNote.id);
          await storage.table('notes').setItem(serverNote.id, serverNoteWithContent);
          continue;
        }

        if (localChange && localChange.type === 'update') {
          await storage.table('sync').removeItem(localChange.id);
          requests.push(NotesHttpClient.update(localNote));
          continue;
        }

        if (localChange && localChange.type === 'delete') {
          requests.push(NotesHttpClient.delete(serverNote.id));
          await storage.table('sync').removeItem(localChange.id);
          await storage.table('notes').removeItem(localChange.id);
        }

        const localChanged = moment(localNote.updated_at);
        const serverChanged = moment(serverNote.updated_at);

        if (localChange && serverChanged.isAfter(localChanged)) {
          const serverNoteWithContent = await NotesHttpClient.retrieve(serverNote.id);
          await storage.table('notes').setItem(serverNote.id, serverNoteWithContent);
          await storage.table('sync').removeItem(localChange.id);
        }
      }

      for (let id in localNotes) {
        const localNote = localNotes[id];
        const localChange = localChanges[id];
        const serverNote = serverNotes.find((serverNote) => serverNote.id === id);

        if (localChange && localChange.type === 'create') {
          requests.push(NotesHttpClient.create(localNote));
          await storage.table('sync').removeItem(id);
          await storage.table('notes').setItem(localNote.id, localNote);
          continue;
        }

        if (!serverNote) {
          await storage.table('notes').removeItem(id);
          await storage.table('sync').removeItem(id);
          continue;
        }

        const localChanged = moment(localNote.updated_at);
        const serverChanged = moment(serverNote.updated_at);

        if (localChanged.isAfter(serverChanged)) {
          requests.push(NotesHttpClient.update(localNote));
        }
      }
    } catch (error) {
      this.syncErrors++;
      application.error(translate('notes.syncFailure'));
      console.error(error);
    }

    Promise.all(requests).then(() => {
      this.list();
    });

    setTimeout(() => this.sync(), 30000);
  }
}

decorate(NotesStore, {
  saving: observable,
  filter: observable,
  sort: observable,
  noteList: observable,
  id: observable,
  title: observable,
  content: observable,
  pinned: observable,
  createdAt: observable,
  updatedAt: observable
});

export default new NotesStore();
