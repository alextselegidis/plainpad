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
import {inject, observer} from 'mobx-react';
import {Redirect} from 'react-router-dom';
import {translate} from '../../lang';

class Settings extends Component {
  componentDidMount() {
    const {
      settings,
    } = this.props;

    settings.fetch();
  }

  render() {
    const {
      settings,
      application
    } = this.props;

    const {
      online
    } = application;

    if (!online) {
      application.warning(translate('layout.pageNotAvailableOffline'));
      return <Redirect to="/notes"/>;
    }

    const {
      defaultLocale,
      mailDriver,
      mailHost,
      mailPort,
      mailUsername,
      mailPassword,
      mailEncryption,
      mailFromAddress,
      mailFromName,
    } = settings;

    const {
      config
    } = application;

    return (
      <div className="my-5 animated fadeIn">
        <Form onSubmit={(event) => {
          event.preventDefault();
          settings.save();
        }}>
          <Row>
            <Col sm="6">
              <Card>
                <CardHeader>
                  <FormattedMessage id="settings.settings"/>
                </CardHeader>
                <CardBody>
                  <FormGroup>
                    <Label>
                      <FormattedMessage id="settings.defaultLocale"/>
                    </Label>
                    <Input type="select" value={defaultLocale}
                           onChange={(event) => settings.defaultLocale = event.target.value}>
                      <option value="en-US">
                        en-US
                      </option>
                      <option value="de-DE">
                        de-DE
                      </option>
                    </Input>
                  </FormGroup>
                </CardBody>
                <CardFooter>
                  <Button type="submit" color="primary" className="float-right">
                    <i className="fa fa-save mr-2"/>
                    <FormattedMessage id="settings.save"/>
                  </Button>
                </CardFooter>
              </Card>

              <Card hidden={!config || !config.updates || !config.updates.length}>
                <CardHeader>
                  <FormattedMessage id="settings.updates"/>
                </CardHeader>
                <CardBody>
                  <p>
                    <FormattedMessage id="settings.newUpdatesAvailable"/>
                  </p>

                  <ul>
                    {config && config.updates ? config.updates.map((update) => <li key={update}>{update}</li>) : null}
                  </ul>

                  <p>
                    <FormattedMessage id="settings.beforeUpdatingMakeSure"/>
                  </p>
                </CardBody>
                <CardFooter>
                  <Button type="button" color="primary" className="float-right"
                          onClick={() => application.update()}>
                    <i className="fa fa-arrow-circle-up mr-2"/>
                    <FormattedMessage id="settings.update"/>
                  </Button>
                </CardFooter>
              </Card>
            </Col>

            <Col sm="6">
              <Card>
                <CardHeader>
                  <FormattedMessage id="settings.mail"/>
                </CardHeader>
                <CardBody>
                  <FormGroup>
                    <Label>
                      <FormattedMessage id="settings.driver"/>
                    </Label>
                    <Input placeholder="smtp" value={mailDriver}
                           onChange={(event) => settings.mailDriver = event.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="settings.host"/>
                    </Label>
                    <Input placeholder="smtp.example.org" value={mailHost}
                           onChange={(event) => settings.mailHost = event.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="settings.port"/>
                    </Label>
                    <Input placeholder="2525" value={mailPort}
                           onChange={(event) => settings.mailPort = event.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="settings.username"/>
                    </Label>
                    <Input placeholder="Username" value={mailUsername}
                           onChange={(event) => settings.mailUsername = event.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="settings.password"/>
                    </Label>
                    <Input type="password" value={mailPassword} placeholder="Password" autoComplete="new-password"
                           onChange={(event) => settings.mailPassword = event.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="settings.encryption"/>
                    </Label>
                    <Input placeholder="tls" value={mailEncryption}
                           onChange={(event) => settings.defaultLocale = event.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="settings.fromAddress"/>
                    </Label>
                    <Input placeholder="from@example.org" value={mailFromAddress}
                           onChange={(event) => settings.mailFromAddress = event.target.value}/>
                  </FormGroup>

                  <FormGroup>
                    <Label>
                      <FormattedMessage id="settings.fromName"/>
                    </Label>
                    <Input placeholder="Plainpad" value={mailFromName}
                           onChange={(event) => settings.mailFromName = event.target.value}/>
                  </FormGroup>
                </CardBody>
                <CardFooter>
                  <Button type="submit" color="primary" className="float-right">
                    <i className="fa fa-save mr-2"/>
                    <FormattedMessage id="settings.save"/>
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

export default inject('settings', 'application')(
  observer(Settings)
);
