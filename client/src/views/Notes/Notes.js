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
import {Input} from 'reactstrap';
import WelcomeMessage from './WelcomeMessage';

class Notes extends Component {
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
      saving,
      content,
    } = notes;

    if (!id && !saving) {
      return <WelcomeMessage/>;
    }

    const {
      user
    } = account;

    return (
      <div className="animated fadeIn h-100">
        <Input
          className={`note-content h-100a w-100 ${user.line === 'full' ? 'full-line' : 'narrow-line'} border-0 rounded-0 shadow-none`}
          type="textarea" value={content} onChange={(event) => notes.updateContent(event.target.value)}
          onFocus={(event) => event.target.value = event.target.value === 'New note ...' ? '' : event.target.value}
        />
      </div>
    );
  }
}

export default inject('account', 'notes')(
  observer(Notes)
);
