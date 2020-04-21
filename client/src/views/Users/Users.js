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
import {Button, Card, CardBody, CardHeader, Col, Input, Row, Table} from 'reactstrap';
import {inject, observer} from 'mobx-react';
import {FormattedMessage} from 'react-intl';
import UsersModal from './UsersModal';
import {translate} from '../../lang';
import {Redirect} from 'react-router-dom';

class Users extends Component {
  componentDidMount() {
    const {
      users
    } = this.props;

    users.list();
  }

  render() {
    const {
      application,
      users
    } = this.props;

    const {
      online
    } = application;

    if (!online) {
      application.warning(translate('layout.pageNotAvailableOffline'));
      return <Redirect to="/notes" />;
    }

    const {
      userList,
      filter
    } = users;

    const rows = userList.map((user) => {
      return <tr key={user.id}>
        <td>
          {user.name}
        </td>

        <td>
          {user.email}
        </td>

        <td>
          {user.admin ? translate('users.admin') : translate('users.user')}
        </td>

        <td>
          <Button size="sm" color="light" className="d-xs-block d-sm-inline-block mb-2 mr-0 mr-sm-2 mb-sm-0" onClick={() => users.edit(user)}>
            <i className="fa fa-pencil mr-2"/>
            <FormattedMessage id="users.edit"/>
          </Button>

          <Button size="sm" color="light" className="d-xs-block d-sm-inline-block" onClick={() => users.delete(user)}>
            <i className="fa fa-trash mr-2"/>
            <FormattedMessage id="users.delete"/>
          </Button>
        </td>
      </tr>
    });

    return (
      <div className="my-5 animated fadeIn users">
        <Row>
          <Col lg="10" xl="8">
            <Card>
              <CardHeader>
                <FormattedMessage id="users.users"/>
              </CardHeader>
              <CardBody>
                <div className="overflow-auto mb-4">
                  <Input placeholder={translate('users.filterUsers')} className="d-inline-block w-50" value={filter} onChange={(event) => {
                    users.filter = event.target.value;
                    setTimeout(() => users.list(), 500);
                  }}/>

                  <Button className="float-right" color="success" onClick={() => users.add()}>
                    <i className="fa fa-plus mr-2"/>
                    <FormattedMessage id="users.add"/>
                  </Button>
                </div>

                <Table responsive hover>
                  <thead>
                  <tr>
                    <th scope="col">
                      <FormattedMessage id="users.name"/>
                    </th>
                    <th scope="col">
                      <FormattedMessage id="users.email"/>
                    </th>
                    <th scope="col">
                      <FormattedMessage id="users.role"/>
                    </th>
                    <th scope="col">
                      <FormattedMessage id="users.actions"/>
                    </th>
                  </tr>
                  </thead>
                  <tbody>
                  {rows}
                  </tbody>
                </Table>
              </CardBody>
            </Card>
          </Col>
        </Row>

        <UsersModal/>
      </div>
    )
  }
}

export default inject('users', 'application')(
  observer(Users)
);
