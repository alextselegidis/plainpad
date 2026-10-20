/*
  Plainpad - Self Hosted Note Taking App

  Copyright (C) Alex Tselegidis - https://alextselegidis.com

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
  _title = '';
  content = '';
  pinned = false;
  createdAt = null;
  updatedAt = null;
  syncErrors = 0;
  syncPromise = null;

  get title() {
    return this._title;
  }

  set title(value) {
    this._title = value;
    this.updateDocumentTitle(value);
  }

  updateDocumentTitle(title) {
    if (typeof document !== 'undefined' && document && document.title !== undefined) {
      const rawTitle = title || '';
      const display = rawTitle.length > 40 ? rawTitle.substring(0, 40) + '...' : rawTitle;
      document.title = (display ? display + ' | ' : '') + 'Plainpad';
    }
  }

  async list() {
    const localNotes = [];

    await storage.table('notes').iterate((value) => {
      localNotes.push(value);
    });

    const filteredNotes = this.filterNotes(localNotes);

    this.noteList = this.sortNotes(filteredNotes);
  }

  // Refresh the sidebar after a change to a single note, without reading every note from IndexedDB like list().
  updateListItem(note, remove = false) {
    const others = this.noteList.filter((item) => item.id !== note.id);

    this.noteList = this.sortNotes(remove ? others : [...others, ...this.filterNotes([note])]);
  }

  async add(title = '') {
    this.id = null;
    this.title = title || this.filter || 'New Note ...';
    this.content = this.title;
    this.pinned = false;
    this.createdAt = null;
    this.updatedAt = null;
    await this.save();
  }

  async select(id) {
    const isSameNote = this.id === id;

    if (isSameNote) {
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

  // TODO: Ensure that the following method is not required and then remove it

  // async refresh() {
  //   const localNote = await storage.table('notes').getItem(this.id);
  //
  //   if (!localNote) {
  //     application.warning(translate('notes.notFoundOrNotSynced'));
  //     return;
  //   }
  //
  //   this.title = localNote.title;
  //   this.content = localNote.content;
  //   this.pinned = localNote.pinned;
  //   this.createdAt = localNote.created_at;
  //   this.updatedAt = localNote.updated_at;
  // }

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

    this.updateListItem({...localNote, ...note});

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

    this.updateListItem({id: this.id}, true);
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

    this.updateListItem(localNote);
  }

  async unpin() {
    this.pinned = false;

    const localNote = await storage.table('notes').getItem(this.id);
    localNote.pinned = false;
    await storage.table('notes').setItem(this.id, localNote);

    await this.commitChange(this.id, 'update');

    this.updateListItem(localNote);
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

    // Reset browser title when notes reset
    if (typeof document !== 'undefined' && document && document.title !== undefined) {
      document.title = 'Plainpad';
    }
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
    // Plain substring match, the search text may contain characters like "(" that break a RegExp.
    const filter = this.filter.toLowerCase();

    return notes.filter((note) => {
      return note.title.toLowerCase().includes(filter) || note.content.toLowerCase().includes(filter);
    });
  }

  sortByDateDescending(notes, field) {
    // Parsed once per note, parsing inside the comparator costs n log n parses on every list refresh.
    const times = new Map(notes.map((note) => [note, Date.parse(note[field]) || 0]));

    return notes.sort((a, b) => times.get(b) - times.get(a));
  }

  sortNotes(notes) {
    let sortedNotes;

    switch (account.user.sort) {
      default:
      case 'modified':
        sortedNotes = this.sortByDateDescending(notes, 'updated_at');
        break;

      case 'created':
        sortedNotes = this.sortByDateDescending(notes, 'created_at');
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
    const pendingChange = await storage.table('sync').getItem(id);

    // A note that never reached the server must stay a "create", or the sync drops it as missing on the server.
    if (type === 'update' && pendingChange && pendingChange.type === 'create') {
      type = 'create';
    }

    await storage.table('sync').setItem(id, {
      date: moment.utc().toDate(),
      type
    });
  }

  async resolveChange(id) {
    await storage.table('sync').removeItem(id);
  }

  // Shared by every caller, so that e.g. logout can wait for a sync that is already running.
  sync() {
    if (!this.syncPromise) {
      this.syncPromise = this.runSync().finally(() => {
        this.syncPromise = null;
      });
    }

    return this.syncPromise;
  }

  async runSync() {
    clearTimeout(this.syncTimeout);

    if (!navigator.onLine || !account.user) {
      this.syncTimeout = setTimeout(() => this.sync(), 60000);
      return;
    }

    await this.list();

    try {
      this.syncing = true;
      const serverNotes = await this.fetchServerNotes();
      const [syncedServerNotes, failedChanges] = await this.syncLocalChanges(serverNotes);
      await this.syncServerChanges(syncedServerNotes);

      if (failedChanges) {
        throw new Error(`${failedChanges} local change(s) failed to sync.`);
      }

      this.syncErrors = 0;
    } catch (error) {
      this.syncErrors++;
      console.error(error);

      if (this.syncErrors < 10) {
        application.error(translate('notes.syncFailure'));
      } else if (this.syncErrors === 10) {
        application.warning(translate('notes.syncingGotDisabled'));
      }
    }

    await this.list();
    this.syncing = false;

    // Keep retrying after repeated failures, just less often, so the pending changes are not stuck until a reload.
    this.syncTimeout = setTimeout(() => this.sync(), this.syncErrors >= 10 ? 600000 : 60000);
  }

  async syncLocalChanges(serverNotes) {
    const localChanges = {};

    await storage.table('sync').iterate((localChange, id) => {
      localChanges[id] = localChange;
    });

    let failedChanges = 0;

    // Run through the local changes and resolve any pending ones. A failing change stays pending for the next sync
    // without blocking the others.
    for (let id in localChanges) {
      const localChange = localChanges[id];
      const localNote = await storage.table('notes').getItem(id);
      const serverNote = serverNotes.find((serverNote) => serverNote.id === id);

      try {
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
      } catch (error) {
        failedChanges++;
        console.error(error);
      }
    }

    return [serverNotes, failedChanges];
  }

  async syncServerChanges(serverNotes) {
    // Read the local notes once, one IndexedDB read per note on every sync gets slow with thousands of notes.
    const localNotes = {};

    await storage.table('notes').iterate((note, id) => {
      localNotes[id] = note;
    });

    const newer = (a, b) => Date.parse(a.updated_at) > Date.parse(b.updated_at);
    const changedOnServer = serverNotes.filter((serverNote) => {
      return !localNotes[serverNote.id] || newer(serverNote, localNotes[serverNote.id]);
    });

    // A first sync (new device, cleared cache) needs the content of every note. One request per note runs into the
    // API rate limit with a few hundred notes, so read the full list page by page instead (one huge response could
    // exceed the PHP memory limit with thousands of notes).
    const contents = {};

    if (changedOnServer.length > 20) {
      for (let page = 1; ; page++) {
        const notes = await NotesHttpClient.list(null, 'id', 'asc', page, 500);

        notes.forEach((note) => contents[note.id] = note);

        if (notes.length < 500) {
          break;
        }
      }
    }

    for (let serverNote of changedOnServer) {
      const serverNoteWithContent = contents[serverNote.id] || await NotesHttpClient.retrieve(serverNote.id);
      await storage.table('notes').setItem(serverNote.id, serverNoteWithContent);
    }

    for (let serverNote of serverNotes) {
      const localNote = localNotes[serverNote.id];

      if (localNote && newer(localNote, serverNote)) {
        await this.uploadNote(localNote);
      }
    }
  }

  async uploadNote(localNote) {
    const serverNote = await NotesHttpClient.update(localNote);

    // Keep the server copy and its updated_at, otherwise the next sync sees the note as changed again (and uploads it
    // on every sync when the device clock is ahead). Skipped when the note was edited in the meantime.
    const currentNote = await storage.table('notes').getItem(localNote.id);

    if (currentNote && currentNote.updated_at === localNote.updated_at) {
      await storage.table('notes').setItem(serverNote.id, serverNote);
    }

    return serverNote;
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
    } else if (serverChanged.isBefore(localChanged)) {
      const uploadedNote = await this.uploadNote(localNote);
      for (let index in serverNotes) {
        if (serverNotes.hasOwnProperty(index) && serverNotes[index].id === localNote.id) {
          serverNotes[index] = uploadedNote;
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
  noteList: observable.ref, // Replaced as a whole on every refresh, deep observables of all notes are not needed.
  id: observable,
  _title: observable,
  content: observable,
  pinned: observable,
  createdAt: observable,
  updatedAt: observable
});

export default new NotesStore();
