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
import {Card, CardBody, CardHeader, Col, Row} from 'reactstrap';
import {inject, observer} from 'mobx-react';

class Help extends Component {
  render() {
    const {
      account
    } = this.props;

    const {
      user
    } = account;

    return (
      <div className="my-5 animated fadeIn">
        <Row>
          <Col sm="6">
            <Card>
              <CardHeader>
                Account
              </CardHeader>
              <CardBody>
                <p>
                  You can perform the following actions with your Plainpad account:
                </p>

                <ul>
                  <li>Login</li>
                  <li>Logout</li>
                  <li>Recover Password</li>
                  <li>Manage Notes</li>
                  <li>Update Profile</li>
                </ul>

                <p>
                  As an administrator you gain access to additional administrative content.
                </p>

                <p>
                  New accounts can only be created by admin users.
                </p>
              </CardBody>
            </Card>
          </Col>

          <Col sm="6">
            <Card>
              <CardHeader>
                Profile
              </CardHeader>
              <CardBody>
                <p>
                  Plainpad allows to any user to make certain changes to their profile. Currently the following aspects
                  can be configured:
                </p>

                <ul>
                  <li>Name</li>
                  <li>Email</li>
                  <li>Password</li>
                  <li>View</li>
                  <li>Line</li>
                  <li>Sort</li>
                  <li>Theme</li>
                </ul>

                <p>
                  View allows the user to change the notes list view and can be set to Comfortable and Compact.
                </p>

                <p>
                  Line allows the user to change the width of the editor so that you get full width lines or narrow
                  lines.
                </p>

                <p>
                  Sort allows the user to set the way the notes must be sorted in the notes list (left side).
                </p>

                <p>
                  Theme allows the user to choose between a light theme or a dark theme.
                </p>

                <p>
                  Always make sure you save your changes in order to take effect.
                </p>
              </CardBody>
            </Card>
          </Col>

          <Col sm="6">
            <Card>
              <CardHeader>
                Notes
              </CardHeader>
              <CardBody>
                <p>
                  The notes are the core entity of the application and any user can create any number of notes within
                  Plainpad. The editor was kept simple in order to allow you focus on the content and not the styling.
                </p>

                <p>
                  The first line of the note will serve as the note title, so you might want to add a descriptive line
                  to be used in the notes list.
                </p>

                <p>
                  By opening the aside menu (on the right) you will be able to find information and actions regarding
                  the selected note. Such meta content becomes available as soon as you select a note and contains the
                  following entries:
                </p>

                <ul>
                  <li>Created Date</li>
                  <li>Updated Date</li>
                  <li>Pin/Unpin</li>
                  <li>Download</li>
                  <li>Delete</li>
                </ul>

                <p>
                  Plainpad allows you to pin notes so that they are always displayed at the top of the notes list. Pin
                  notes that you use the most so that you can find them easily.
                </p>

                <p>
                  Downloading notes is possible in a txt format file containing all the contents of the note.
                </p>

                <p>
                  You can of course delete notes as well, but be careful as this action is irreversible.
                </p>
              </CardBody>
            </Card>
          </Col>

          <Col sm="6">
            <Card hidden={user && user.admin}>
              <CardHeader>
                Users
              </CardHeader>
              <CardBody>
                <p>
                  After the Plainpad installation there is a single admin user created but adding more users is still
                  possible.
                </p>

                <p>
                  The users page is only visible to admin users and enables the user management.
                </p>

                <p>
                  In this page you can also reset passwords in case this is not possible through the password recovery
                  form.
                </p>
              </CardBody>
            </Card>

            <Card>
              <CardHeader>
                Settings
              </CardHeader>
              <CardBody>
                <p>
                  In the settings page admins are able to change all the app related settings. These are system wide
                  settings and are being applied to all users.
                </p>
              </CardBody>
            </Card>
          </Col>
        </Row>
      </div>
    );
  }
}

export default inject('account')(
  observer(Help)
);
