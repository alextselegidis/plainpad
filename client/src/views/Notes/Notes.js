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

import React, {Component} from 'react';
import {inject, observer} from 'mobx-react';
import TextareaAutosize from 'react-textarea-autosize';
import WelcomeMessage from './WelcomeMessage';

class Notes extends Component {
  textarea = null;
  scrollY = null;

  componentDidMount() {
    const {
      account,
      notes,
      match
    } = this.props;

    if (!account.session) {
      return;
    }

    const id = match.params.id;

    if (id) {
      notes.select(id);
    }
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    const {
      notes,
      match
    } = this.props;

    const id = match.params.id;
    const previousId = prevProps.match.params.id;

    if (id && id !== previousId) {
      notes.select(id);
    }
  }

  componentWillUnmount() {
    this.props.notes.reset();
  }

  render() {
    const {
      account,
      notes
    } = this.props;

    const {
      id,
      content,
    } = notes;

    if (!id && !content) {
      return <WelcomeMessage/>;
    }

    const {
      user
    } = account;

    return (
      <div className="animated fadeIn h-100 py-2">
        <TextareaAutosize
          ref={(tag) => this.textarea = tag}
          className={`note-content ${user.line === 'full' ? 'full-line' : 'narrow-line'}`}
          value={content}
          onKeyDown={() => this.scrollY = window.scrollY}
          onChange={(event) => notes.updateContent(event.target.value)}
          onHeightChange={(height) => notes.applyScrollFix(this.textarea, this.scrollY, height)}
        />
      </div>
    );
  }
}

export default inject('account', 'notes')(
  observer(Notes)
);
