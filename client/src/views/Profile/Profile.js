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
import {Button, Card, CardBody, CardFooter, CardHeader, Col, Form, FormGroup, Input, Label, Row} from 'reactstrap';
import {FormattedMessage} from 'react-intl';
import {translate} from '../../lang';
import {inject, observer} from 'mobx-react';

class Profile extends Component {
  componentDidMount() {
    const {
      profile
    } = this.props;

    profile.load();
  }

  render() {
    const {
      application,
      account,
      profile,
    } = this.props;

    const {
      installation,
      online
    } = application;

    const {
      user
    } = account;

    const {
      name,
      email,
      password,
      passwordConfirmation,
      locale,
      view,
      line,
      sort,
      theme,
      encrypt,
    } = profile;

    return (
      <div className="my-5 animated fadeIn">
        <Form onSubmit={(event) => {
          event.preventDefault();
          profile.save();
        }}>
          <Row>
            <Col sm="6">
              <Card>
                <CardHeader>
                  <FormattedMessage id="profile.profile"/>
                </CardHeader>
                <CardBody>
                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.name"/>
                    </Label>
                    <Input value={name}
                           onChange={(event) => profile.name = event.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.email"/>
                    </Label>
                    <Input type="email" value={email}
                           onChange={(event) => profile.email = event.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.password"/>
                    </Label>
                    <Input type="password" value={password} autoComplete="new-password"
                           onChange={(event) => profile.password = event.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.passwordConfirmation"/>
                    </Label>
                    <Input type="password" value={passwordConfirmation}
                           onChange={(event) => profile.passwordConfirmation = event.target.value}/>
                  </FormGroup>
                </CardBody>
                <CardFooter>
                  <Button type="submit" color="primary" className="float-right">
                    <i className="fa fa-save mr-2"/>
                    <FormattedMessage id="profile.save"/>
                  </Button>
                </CardFooter>
              </Card>

              <Card hidden={!installation}>
                <CardHeader>
                  <FormattedMessage id="profile.application"/>
                </CardHeader>
                <CardBody>
                  <p>
                    <FormattedMessage id="profile.youCanInstallPlainpad"/>
                  </p>

                  <Button onClick={() => application.install()}>
                    <i className="fa fa-cloud-download mr-2"/>
                    <FormattedMessage id="profile.install"/>
                  </Button>
                </CardBody>
              </Card>
            </Col>

            <Col sm="6">
              <Card>
                <CardHeader>
                  <FormattedMessage id="profile.settings"/>
                </CardHeader>
                <CardBody>
                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.locale"/>
                    </Label>
                    <Input type="select" value={locale}
                           onChange={(event) => profile.locale = event.target.value}>
                      <option value="en-US">
                        en-US
                      </option>
                      <option value="de-DE">
                        de-DE
                      </option>
                    </Input>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.view"/>
                    </Label>
                    <Input type="select" value={view}
                           onChange={(event) => profile.view = event.target.value}>
                      <option value="comfortable">
                        {translate('profile.comfortable')}
                      </option>
                      <option value="compact">
                        {translate('profile.compact')}
                      </option>
                    </Input>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.line"/>
                    </Label>
                    <Input type="select" value={line}
                           onChange={(event) => profile.line = event.target.value}>
                      <option value="full">
                        {translate('profile.full')}
                      </option>
                      <option value="narrow">
                        {translate('profile.narrow')}
                      </option>
                    </Input>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.sort"/>
                    </Label>
                    <Input type="select" value={sort}
                           onChange={(event) => profile.sort = event.target.value}>
                      <option value="modified">
                        {translate('profile.lastModifiedFirst')}
                      </option>
                      <option value="created">
                        {translate('profile.lastCreatedFirst')}
                      </option>
                      <option value="alphabetical">
                        {translate('profile.alphabetically')}
                      </option>
                    </Input>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.theme"/>
                    </Label>
                    <Input type="select" value={theme}
                           onChange={(event) => profile.theme = event.target.value}>
                      <option value="light">
                        {translate('profile.light')}
                      </option>
                      <option value="dark">
                        {translate('profile.dark')}
                      </option>
                    </Input>
                  </FormGroup>
                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.encrypt"/>
                    </Label>
                    <Input type="select" value={encrypt ? '1' : '0'}
                           onChange={(event) => profile.encrypt = event.target.value === '1'}>
                      <option value="1">
                        {translate('profile.yes')}
                      </option>
                      <option value="0">
                        {translate('profile.no')}
                      </option>
                    </Input>
                  </FormGroup>
                  <FormGroup className="text-center py-4">
                    <Button hidden={!online} size="lg" color={user.theme === 'light' ? 'primary' : 'secondary'}
                            outline={true} onClick={() => profile.invalidateCache()}>
                      <i className="fa fa-eraser mr-2"/>
                      <FormattedMessage id="profile.invalidateCache"/>
                    </Button>
                  </FormGroup>
                </CardBody>
                <CardFooter>
                  <Button type="submit" color="primary" className="float-right">
                    <i className="fa fa-save mr-2"/>
                    <FormattedMessage id="profile.save"/>
                  </Button>
                </CardFooter>
              </Card>
            </Col>
          </Row>
        </Form>
      </div>
    );
  }
}

export default inject('account', 'application', 'profile')(
  observer(Profile)
);
