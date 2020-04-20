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
import {decorate, observable} from 'mobx';

class Profile extends Component {
  name = '';
  email = '';
  password = '';
  passwordConfirmation = '';
  locale = 'en-US';
  view = 'compact';
  line = 'full';
  sort = 'modified';
  theme = 'light';
  encrypt = '0';

  constructor(props) {
    super(props);

    const {
      account
    } = props;

    const {
      user
    } = account;

    if (user) {
      this.name = user.name;
      this.email = user.email;
      this.locale = user.locale;
      this.view = user.view;
      this.line = user.line;
      this.sort = user.sort;
      this.theme = user.theme;
      this.encrypt = user.encrypt;
    }
  }

  render() {
    const {
      account,
      application,
    } = this.props;

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
    } = this;

    const {
      installation,
      online
    } = application;

    const {
      user
    } = account;

    return (
      <div className="my-5 animated fadeIn">
        <Form onSubmit={(event) => {
          event.preventDefault();
          account.save({
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
          });
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
                           onChange={(e) => this.name = e.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.email"/>
                    </Label>
                    <Input type="email" placeholder="username@example.org" value={email}
                           onChange={(e) => this.email = e.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.password"/>
                    </Label>
                    <Input type="password" placeholder="" value={password} autoComplete="new-password"
                           onChange={(e) => this.password = e.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="profile.passwordConfirmation"/>
                    </Label>
                    <Input type="password" placeholder="" value={passwordConfirmation}
                           onChange={(e) => this.passwordConfirmation = e.target.value}/>
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
                           onChange={(e) => this.locale = e.target.value}>
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
                           onChange={(e) => this.view = e.target.value}>
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
                           onChange={(e) => this.line = e.target.value}>
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
                           onChange={(e) => this.sort = e.target.value}>
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
                           onChange={(event) => this.theme = event.target.value}>
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
                           onChange={(event) => this.encrypt = event.target.value}>
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
                            outline={true} onClick={() => account.invalidateCache()}>
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

decorate(Profile, {
  name: observable,
  email: observable,
  password: observable,
  passwordConfirmation: observable,
  locale: observable,
  view: observable,
  line: observable,
  sort: observable,
  theme: observable,
  encrypt: observable,
});

export default inject('account', 'application')(
  observer(Profile)
);
