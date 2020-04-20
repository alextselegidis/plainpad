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
import {Button, Jumbotron} from 'reactstrap';
import {FormattedMessage} from 'react-intl';

class WelcomeMessage extends Component {
  render() {
    const {
      notes
    } = this.props;

    return (
      <div className="animated fadeIn h-100 py-5">
        <Jumbotron className="bg-white rounded-0 text-center welcome-message my-5">
          <h1 className="display-5">
            <FormattedMessage id="notes.welcome"/>
          </h1>

          <p className="lead mb-4">
            <FormattedMessage id="notes.selectNoteOrCreate"/>
          </p>

          <Button color="primary" size="lg" onClick={() => notes.add()}>
            <i className="fa fa-plus mr-2"/>
            <FormattedMessage id="notes.newNote"/>
          </Button>
        </Jumbotron>
      </div>
    )
  }
}

export default inject('notes')(
  observer(WelcomeMessage)
);
