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
import {translate} from '../../i18n';
import {inject, observer} from 'mobx-react';

class Profile extends Component {
  render() {
    const {
      accountStore,
      applicationStore,
    } = this.props;

    const {
      profile
    } = accountStore;

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

    const {
      installation,
      online
    } = this.props.applicationStore;

    return (
      <div className="my-5 animated fadeIn">
        <Form onSubmit={(event) => {
          event.preventDefault();
          accountStore.save();
        }}>
          <Row>
            <Col sm={6}>
              <Card>
                <CardHeader>
                  <FormattedMessage id="profile.profile"/>
                </CardHeader>
                <CardBody>
                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.name"/>
                    </Label>
                    <Input placeholder="Name" value={name}
                           onChange={(e) => profile.name = e.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.email"/>
                    </Label>
                    <Input type="email" placeholder="username@example.org" value={email}
                           onChange={(e) => profile.email = e.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.password"/>
                    </Label>
                    <Input type="password" placeholder="" value={password} autoComplete="new-password"
                           onChange={(e) => profile.password = e.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.passwordConfirmation"/>
                    </Label>
                    <Input type="password" placeholder="" value={passwordConfirmation}
                           onChange={(e) => profile.passwordConfirmation = e.target.value}/>
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
                    <FormattedMessage id="profile.youCanInstallPlainpad" />
                  </p>

                  <Button onClick={() => applicationStore.install()}>
                    <i className="fa fa-cloud-download mr-2"/>
                    <FormattedMessage id="profile.install" />
                  </Button>
                </CardBody>
              </Card>
            </Col>

            <Col sm={6}>
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
                           onChange={(e) => profile.locale = e.target.value}>
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
                           onChange={(e) => profile.view = e.target.value}>
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
                           onChange={(e) => profile.line = e.target.value}>
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
                           onChange={(e) => profile.sort = e.target.value}>
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
                    <Input type="select" value={encrypt === true || encrypt === '1' ? '1' : '0'}
                           onChange={(event) => profile.encrypt = event.target.value}>
                      <option value="1">
                        {translate('profile.yes')}
                      </option>
                      <option value="0">
                        {translate('profile.no')}
                      </option>
                    </Input>
                  </FormGroup>
                  <FormGroup>
                    <Button className="secondary" hidden={!online}
                            onClick={() => accountStore.invalidateCache()}>
                      <FormattedMessage id="profile.invalidateCache" />
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

export default inject('accountStore', 'applicationStore')(
  observer(Profile)
);
