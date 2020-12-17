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
import {Button, Form, FormGroup, Input, Label, Modal, ModalBody, ModalFooter, ModalHeader,} from 'reactstrap';
import {inject, observer} from 'mobx-react';
import {FormattedMessage} from 'react-intl';
import {translate} from '../../lang';

class UsersModal extends Component {
  toggle() {
    const {
      users
    } = this.props;

    users.mode = 'list';
  }

  render() {
    const {
      users
    } = this.props;

    const {
      mode,
      name,
      email,
      password,
      passwordConfirmation,
      admin,
    } = users;

    const isOpen = ['add', 'edit'].includes(mode);

    if (!isOpen) {
      return <div/>;
    }

    return (
      <Modal isOpen={isOpen} toggle={() => this.toggle()}>
        <Form onSubmit={(event) => {
          event.preventDefault();
          users.save();
        }}>
          <ModalHeader toggle={() => this.toggle()}>
            <FormattedMessage id="users.user"/>
          </ModalHeader>
          <ModalBody>

            <FormGroup>
              <Label>
                <FormattedMessage id="users.name"/>
              </Label>
              <Input value={name}
                     onChange={(event) => users.name = event.target.value}/>
            </FormGroup>

            <FormGroup>
              <Label>
                <FormattedMessage id="users.email"/>
              </Label>
              <Input type="email" value={email}
                     onChange={(event) => users.email = event.target.value}/>
            </FormGroup>

            <FormGroup>
              <Label>
                <FormattedMessage id="users.password"/>
              </Label>
              <Input type="password" value={password} autoComplete="new-password"
                     onChange={(event) => users.password = event.target.value}/>
            </FormGroup>

            <FormGroup>
              <Label>
                <FormattedMessage id="users.passwordConfirmation"/>
              </Label>
              <Input type="password" value={passwordConfirmation}
                     onChange={(event) => users.passwordConfirmation = event.target.value}/>
            </FormGroup>

            <FormGroup>
              <Label>
                <FormattedMessage id="users.role"/>
              </Label>
              <Input type="select" value={admin ? 'admin' : 'user'}
                     onChange={(event) => users.admin = event.target.value === 'admin'}>
                <option value="user">
                  {translate('users.user')}
                </option>
                <option value="admin">
                  {translate('users.admin')}
                </option>
              </Input>
            </FormGroup>
          </ModalBody>
          <ModalFooter>
            <Button color="light" className="mr-2" onClick={() => this.toggle()}>
              <i className="fa fa-ban mr-2" />
              <FormattedMessage id="users.cancel"/>
            </Button>

            <Button type="submit" color="primary">
              <i className="fa fa-save mr-2" />
              <FormattedMessage id="users.save"/>
            </Button>
          </ModalFooter>
        </Form>
      </Modal>
    )
  }
}

export default inject('users')(
  observer(UsersModal)
);
