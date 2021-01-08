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
import moment from 'moment';
import {FormattedMessage} from 'react-intl';
import {Button, Col} from 'reactstrap';

class NoteAside extends Component {
  render() {
    const {
      session
    } = this.props.account;

    if (!session) {
      return <div/>;
    }

    const {
      id,
      title,
      createdAt,
      updatedAt,
      pinned,
    } = this.props.notes;

    if (!id) {
      return <Col>
        <h5 className="mb-4 mt-4 border-bottom pb-4">
          <FormattedMessage id="notes.selectNote" />
        </h5>

        <p className="mb-4">
          <FormattedMessage id="notes.selectNoteInformation" />
        </p>
      </Col>;
    }

    return (
      <Col>
        <h5 className="mb-4 mt-4 border-bottom pb-4">{title}</h5>

        <p className="mb-4">
          <strong className="mr-2">
            <FormattedMessage id="notes.created"/>
          </strong>
          {moment(createdAt).format('MMM DD, YYYY')}
        </p>

        <p className="mb-4">
          <strong className="mr-2">
            <FormattedMessage id="notes.modified"/>
          </strong>
          {moment(updatedAt).format('MMM DD, YYYY')}
        </p>

        <div className="mb-4 border-bottom" />

        <div className="mb-4">
          <Button color="light" block className="m-auto" onClick={() => this.props.notes.pin()} hidden={pinned}>
            <i className="fa fa-thumb-tack mr-2"/>
            <FormattedMessage id="notes.pin"/>
          </Button>

          <Button color="light" block className="m-auto" onClick={() => this.props.notes.unpin()} hidden={!pinned}>
            <i className="fa fa-thumb-tack mr-2"/>
            <FormattedMessage id="notes.unpin"/>
          </Button>
        </div>

        <div className="mb-4">
          <Button color="light" block className="m-auto" onClick={() => this.props.notes.download()}>
            <i className="fa fa-cloud-download mr-2"/>
            <FormattedMessage id="notes.download"/>
          </Button>
        </div>

        <div className="mb-4" hidden={!navigator.canShare}>
          <Button color="light" block className="m-auto" onClick={() => this.props.notes.share()}>
            <i className="fa fa-share-alt mr-2"/>
            <FormattedMessage id="notes.share"/>
          </Button>
        </div>

        <div className="mb-4">
          <Button color="danger" block className="m-auto" onClick={() => this.props.notes.delete()}>
            <i className="fa fa-trash mr-2"/>
            <FormattedMessage id="notes.delete"/>
          </Button>
        </div>
      </Col>
    );
  }
}

export default inject('account', 'notes')(
  observer(NoteAside)
);
