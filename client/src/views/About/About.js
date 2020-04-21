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
import {Button, Card, CardBody, CardHeader, Col, Row} from 'reactstrap';

class About extends Component {
  render() {
    return (
      <div className="my-5 animated fadeIn">
        <Row>
          <Col lg="8">
            <Card>
              <CardHeader>
                About
              </CardHeader>
              <CardBody>
                <h5>Plainpad</h5>

                <p>
                  With Plainpad you can allow multiple users to access the application without being able to see each
                  other's notes. The notes are being encrypted and stored safely in the database.
                </p>

                <p>
                  Made with simplicity in mind Plainpad feels directly familiar, with the right actions placed in the
                  right positions you will only have to concentrate on the content.
                </p>

                <p>
                  All the contents and notes are being encrypted before being stored so that the maximum security is
                  guaranteed. Only the note owners are able to access the notes in a readable form.
                </p>

                <p>
                  The installation is pretty simple and only requires a web server with PHP and MySQL configured. You
                  can start using the app after filling a small form.
                </p>

                <h5>Stay Tuned</h5>

                <p>
                  Don't lose any updates and get informed about the latest Plainpad updates.
                </p>

                <div className="mb-2">
                  <a href="https://alextselegidis.com/get/plainpad" target="_blank" rel="noopener noreferrer">
                    <Button className="btn-yahoo btn-brand d-block mb-4 d-sm-inline-block mb-sm-0 mr-sm-4">
                      <i className="fa fa-globe"/>
                      <span>Website</span>
                    </Button>
                  </a>

                  <a href="https://twitter.com/AlexTselegidis" target="_blank" rel="noopener noreferrer">
                    <Button className="btn-twitter btn-brand d-block mb-4 d-sm-inline-block mb-sm-0 mr-sm-4">
                      <i className="fa fa-twitter"/>
                      <span>Twitter</span>
                    </Button>
                  </a>

                  <a href="https://github.com/alextselegidis/plainpad" target="_blank" rel="noopener noreferrer">
                    <Button className="btn-github btn-brand d-block d-sm-inline-block">
                      <i className="fa fa-github"/>
                      <span>GitHub</span>
                    </Button>
                  </a>
                </div>
              </CardBody>
            </Card>
          </Col>
        </Row>

      </div>
    );
  }
}

export default About;
