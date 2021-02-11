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
  syncing = false;
  filter = '';
  filterTimeout = null;
  saveTimeout = null;
  syncTimeout = null;
  noteList = [];
  id = null;
  title = '';
  content = '';
  pinned = false;
  createdAt = null;
  updatedAt = null;
  syncErrors = 0;

  async list() {
    const localNotes = [];

    await storage.table('notes').iterate((value) => {
      localNotes.push(value);
    });

    const filteredNotes = this.filterNotes(localNotes);

    this.noteList = this.sortNotes(filteredNotes);
  }

  async add() {
    this.id = null;
    this.title = this.filter || 'New Note ...';
    this.content = this.title;
    this.pinned = false;
    this.createdAt = null;
    this.updatedAt = null;
    await this.save();
  }

  async select(id, force = false) {
    const isSameNote = this.id === id;

    if (isSameNote && !force) {
      return;
    }

    const localNote = await storage.table('notes').getItem(id);

    if (!localNote) {
      application.warning(translate('notes.notFoundOrNotSynced'));
      return;
    }

    window.location.href = `#/notes/${id}`;

    this.id = localNote.id;
    this.title = localNote.title;
    this.content = localNote.content;
    this.pinned = localNote.pinned;
    this.createdAt = localNote.created_at;
    this.updatedAt = localNote.updated_at;

    window.scrollTo(0, 0);
  }

  async save() {
    let id = this.id;

    const isNewNote = id === null;

    if (isNewNote) {
      id = `local-${uuidv4()}`;
    }

    const isLocalNote = id.includes('local');

    const note = {
      id,
      user_id: account.user.id,
      created_at: this.createdAt || moment.utc().format(),
      updated_at: moment.utc().format(),
      title: this.title,
      content: this.content,
      pinned: this.pinned
    };

    const localNote = await storage.table('notes').getItem(id);

    await storage.table('notes').setItem(id, {...localNote, ...note});

    await this.commitChange(id, isLocalNote ? 'create' : 'update');

    await this.list();

    if (isNewNote) {
      window.location.href = `#/notes/${id}`;
    }
  }

  async delete() {
    const result = await Swal.fire({
      text: translate('notes.deletePrompt'),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d94e5c',
      confirmButtonText: translate('notes.delete'),
      reverseButtons: true
    });

    if (!result.value) {
      return;
    }

    await storage.table('notes').removeItem(this.id);
    window.location.href = `#/notes`;
    document.querySelector('.aside-toggler').click(); // Close the sidebar.

    const isLocalNote = this.id.includes('local');

    if (isLocalNote) {
      await this.resolveChange(this.id);
    } else {
      await this.commitChange(this.id, 'delete');
    }

    await this.list();
    this.reset();
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
    this.pinned = true;

    const localNote = await storage.table('notes').getItem(this.id);
    localNote.pinned = true;
    await storage.table('notes').setItem(this.id, localNote);

    await this.commitChange(this.id, 'update');

    await this.list();
  }

  async unpin() {
    this.pinned = false;

    const localNote = await storage.table('notes').getItem(this.id);
    localNote.pinned = false;
    await storage.table('notes').setItem(this.id, localNote);

    await this.commitChange(this.id, 'update');

    await this.list();
  }

  updateContent(content) {
    this.content = content;

    this.title = this.getTitleByContent(content);

    if (this.saveTimeout) {
      clearTimeout(this.saveTimeout);
      this.saveTimeout = null;
    }

    this.saveTimeout = setTimeout(() => {
      this.save();
    }, 1000)
  }

  getTitleByContent(content) {
    let title;

    if (content.includes("\n")) {
      const firstLine = content.match(/^(.*)\n/)[1];
      title = firstLine.substring(0, 40).trim();
    } else {
      title = content.substring(0, 40).trim();
    }

    if (!title) {
      title = 'NO-TITLE';
    }

    return title;
  }

  updateFilter(filter) {
    this.filter = filter;

    if (this.filterTimeout) {
      clearTimeout(this.filterTimeout);
      this.filterTimeout = null;
    }

    this.filterTimeout = setTimeout(() => this.list(), 1000);
  }

  reset() {
    this.id = null;
    this.title = '';
    this.content = '';
    this.pinned = '';
    this.createdAt = null;
    this.updatedAt = null;
  }

  empty() {
    this.noteList = [];
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
      return note.title.match(regex) || note.content.match(regex);
    });
  }

  sortNotes(notes) {
    let sortedNotes;

    switch (account.user.sort) {
      default:
      case 'modified':
        sortedNotes = notes.sort((a, b) => {
          const aModified = moment(a.updated_at);
          const bModified = moment(b.updated_at);

          if (aModified.isAfter(bModified)) {
            return -1;
          }

          if (aModified.isBefore(bModified)) {
            return 1;
          }

          return 0;
        });
        break;

      case 'created':
        sortedNotes = notes.sort((a, b) => {
          const aCreated = moment(a.created_at);
          const bCreated = moment(b.created_at);

          if (aCreated.isBefore(bCreated)) {
            return 1;
          }

          if (aCreated.isAfter(bCreated)) {
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

  async commitChange(id, type) {
    await storage.table('sync').setItem(id, {
      date: moment.utc().toDate(),
      type
    });
  }

  async resolveChange(id) {
    await storage.table('sync').removeItem(id);
  }

  async sync() {
    if (this.syncing) {
      return;
    }

    clearTimeout(this.syncTimeout);

    if (!navigator.onLine || !account.user) {
      this.syncTimeout = setTimeout(() => this.sync(), 60000);
      return;
    }

    await this.list();

    if (this.syncErrors >= 10) {
      application.warning(translate('notes.syncingGotDisabled'));
      return;
    }

    try {
      this.syncing = true;
      const serverNotes = await this.fetchServerNotes();
      const syncedServerNotes = await this.syncLocalChanges(serverNotes);
      await this.syncServerChanges(syncedServerNotes);
    } catch (error) {
      this.syncErrors++;
      application.error(translate('notes.syncFailure'));
      console.error(error);
    }

    await this.list();
    this.syncing = false;
    this.syncTimeout = setTimeout(() => this.sync(), 60000);
  }

  async syncLocalChanges(serverNotes) {
    const localChanges = {};

    await storage.table('sync').iterate((localChange, id) => {
      localChanges[id] = localChange;
    });

    // Run through the local changes and resolve any pending ones.
    for (let id in localChanges) {
      const localChange = localChanges[id];
      const localNote = await storage.table('notes').getItem(id);
      const serverNote = serverNotes.find((serverNote) => serverNote.id === id);

      switch (localChange.type) {
        case 'create':
          serverNotes = await this.syncCreateChange(serverNotes, id, localNote);
          break;
        case 'update':
          serverNotes = await this.syncUpdateChange(serverNotes, id, serverNote, localNote, localChange);
          break;
        case 'delete':
          serverNotes = await this.syncDeleteChange(serverNotes, id, serverNote);
          break;
        default:
        // Invalid change type, do not process anything.
      }

      await this.resolveChange(id);
    }

    return serverNotes;
  }

  async syncServerChanges(serverNotes) {
    // Add new notes that are not present in the local cache.
    for (let serverNote of serverNotes) {
      const localNote = await storage.table('notes').getItem(serverNote.id);

      // Import server note to the local database.
      if (localNote) {
        const localChanged = moment(localNote.updated_at);
        const serverChanged = moment(serverNote.updated_at);

        if (serverChanged.isAfter(localChanged)) {
          const serverNoteWithContent = await NotesHttpClient.retrieve(serverNote.id);
          await storage.table('notes').setItem(serverNote.id, serverNoteWithContent);
          if (this.id === serverNote.id) {
            await this.select(this.id, true);
          }
        } else if (serverChanged.isBefore(localChanged)) {
          await NotesHttpClient.update(localNote);
        }
      } else {
        const serverNoteWithContent = await NotesHttpClient.retrieve(serverNote.id);
        await storage.table('notes').setItem(serverNote.id, serverNoteWithContent);
        if (this.id === serverNote.id) {
          await this.select(this.id, true);
        }
      }
    }
  }

  async fetchServerNotes() {
    return await NotesHttpClient.list(null, null, null, null, null, [
      'id',
      'created_at',
      'updated_at',
      'pinned',
      'title',
      'user_id'
    ]);
  }

  async syncCreateChange(serverNotes, id, localNote) {
    if (!id.includes('local')) {
      await this.resolveChange(id); // This local change is invalid.
      return serverNotes;
    }

    if (!localNote) {
      await this.resolveChange(id); // This local change does not match any local note.
      return serverNotes;
    }

    const newServerNote = await NotesHttpClient.create(localNote);

    await storage.table('notes').setItem(newServerNote.id, newServerNote);

    if (this.id === localNote.id) {
      await this.select(newServerNote.id);
    }

    await storage.table('notes').removeItem(id);

    return [...serverNotes, newServerNote];
  }

  async syncUpdateChange(serverNotes, id, serverNote, localNote, localChange) {
    // Update the server note as it got updated locally (this includes resolving update conflicts by using the
    // latest update and discarding all previous updates).
    if (!serverNote) {
      // The note does not exist on the server and must therefore be removed from the local database.
      await storage.table('notes').removeItem(id);
      await this.resolveChange(id);
      if (this.id === id) {
        window.location.href = `#/notes`;
        await this.reset();
      }
      return serverNotes;
    }

    const localChanged = moment(localChange.date);
    const serverChanged = moment(serverNote.updated_at);

    if (serverChanged.isAfter(localChanged)) {
      const serverNoteWithContent = await NotesHttpClient.retrieve(serverNote.id);
      await storage.table('notes').setItem(serverNote.id, serverNoteWithContent);
      if (this.id === serverNote.id) {
        await this.select(this.id);
      }
    } else if (serverChanged.isBefore(localChanged)) {
      await NotesHttpClient.update(localNote);
      for (let index in serverNotes) {
        if (serverNotes.hasOwnProperty(index) && serverNotes[index].id === localNote.id) {
          serverNotes[index] = localNote;
          break;
        }
      }
    }

    return serverNotes;
  }

  async syncDeleteChange(serverNotes, id, serverNote) {
    // Remove the server note as it got removed locally.
    if (serverNote) {
      await NotesHttpClient.delete(serverNote.id);
      serverNotes = serverNotes.filter((serverNote) => serverNote.id !== id);
    }

    return serverNotes;
  }

  applyScrollFix(textarea, scrollY, height) {
      textarea.style.height = (height + 250) + 'px';
      window.scrollTo(0, scrollY);
  }
}

decorate(NotesStore, {
  syncing: observable,
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
