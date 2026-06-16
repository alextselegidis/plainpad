/*
  Plainpad - Self Hosted Note Taking App

  Copyright (C) Alex Tselegidis - https://alextselegidis.com

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
import {
  Button,
  Card,
  CardBody,
  CardGroup,
  Col,
  Container,
  Form,
  Input,
  InputGroup,
  InputGroupAddon,
  InputGroupText,
  Row
} from 'reactstrap';
import {Redirect} from 'react-router-dom';
import {decorate, observable} from 'mobx';

class ResetPassword extends Component {
  password = '';
  confirm = '';

  render() {
    const {account, location} = this.props;
    const {passwordReset} = account;

    const params = new URLSearchParams(location.search);
    const email = params.get('email') || '';
    const token = params.get('token') || '';

    if (passwordReset) {
      return <Redirect to="/login"/>;
    }

    if (!email || !token) {
      return <Redirect to="/login"/>;
    }

    return (
      <div className="app flex-row align-items-center">
        <Container>
          <Row className="justify-content-center">
            <Col md="8" lg="6">
              <CardGroup>
                <Card className="p-4">
                  <CardBody>
                    <Form onSubmit={(event) => {
                      event.preventDefault();
                      account.resetPassword(email, token, this.password, this.confirm);
                    }}>
                      <h1>Reset Password</h1>
                      <p className="text-muted">Enter your new password</p>
                      <InputGroup className="mb-3">
                        <InputGroupAddon addonType="prepend">
                          <InputGroupText>
                            <i className="icon-lock"/>
                          </InputGroupText>
                        </InputGroupAddon>
                        <Input type="password" placeholder="New Password" autoComplete="new-password"
                               value={this.password}
                               onChange={event => this.password = event.target.value}/>
                      </InputGroup>
                      <InputGroup className="mb-4">
                        <InputGroupAddon addonType="prepend">
                          <InputGroupText>
                            <i className="icon-lock"/>
                          </InputGroupText>
                        </InputGroupAddon>
                        <Input type="password" placeholder="Confirm Password" autoComplete="new-password"
                               value={this.confirm}
                               onChange={event => this.confirm = event.target.value}/>
                      </InputGroup>
                      <Row>
                        <Col xs="6">
                          <Button color="primary" className="px-4">Set Password</Button>
                        </Col>
                      </Row>
                    </Form>
                  </CardBody>
                </Card>
              </CardGroup>
            </Col>
          </Row>
        </Container>
      </div>
    );
  }
}

decorate(ResetPassword, {
  password: observable,
  confirm: observable,
});

export default inject('account')(
  observer(ResetPassword)
);
