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
import {NavLink} from 'react-router-dom';
import {DropdownItem, DropdownMenu, DropdownToggle, Nav, NavItem, UncontrolledDropdown} from 'reactstrap';
import PropTypes from 'prop-types';

import {AppAsideToggler, AppNavbarBrand, AppSidebarToggler} from '@coreui/react';
import logo from '../../assets/img/brand/logo.svg'
import minimizedLogo from '../../assets/img/brand/minimized-logo.svg'
import {inject, observer} from 'mobx-react';
import {FormattedMessage} from 'react-intl';

const propTypes = {
  children: PropTypes.node,
};

const defaultProps = {};

class NoteHeader extends Component {
  render() {
    const {
      account,
      notes,
      application,
      profile,
      onLogout
    } = this.props;

    const {
      syncing
    } = notes;

    const {
      user
    } = account;

    const {
      config,
      online
    } = application;

    return (
      <React.Fragment>
        <AppSidebarToggler className="d-lg-none sidebar-toggler" display="md" mobile/>
        <AppNavbarBrand
          className="d-md-down-none"
          full={{src: logo, width: 32, height: 32, alt: 'Plainpad Logo'}}
          minimized={{src: minimizedLogo, width: 30, height: 30, alt: 'Plainpad Logo'}}
        >
          <img src={logo} alt="Plainpad Logo" width={32} height={32} className="navbar-brand-logo mr-2"/>
          <span className="navbar-brand-title">PLAINPAD</span>
        </AppNavbarBrand>
        <AppSidebarToggler className="d-md-down-none" display="lg"/>

        <Nav className="ml-auto" navbar>
          <NavItem>
            <i className={`fa fa-refresh fa-2x rounded-circle ${syncing ? 'syncing' : ''}`}
               onClick={() => notes.sync()}
            />
          </NavItem>

          <NavItem>
            <i className={`fa ${user.theme === 'light' ? 'fa-moon-o' : 'fa-sun-o'} fa-2x rounded-circle theme-toggler`}
               onClick={() => profile.toggleTheme()}
            />
          </NavItem>

          <UncontrolledDropdown nav direction="down">
            <DropdownToggle nav className={config && config.updates && config.updates.length > 0 ? 'updates-available' : ''}>
              <i className="fa fa-user-circle fa-2x" hidden={config && config.updates && config.updates.length}/>
              <i className="fa fa-arrow-circle-up fa-2x" hidden={!config || !config.updates || !config.updates.length}/>
            </DropdownToggle>
            <DropdownMenu right>
              <DropdownItem header tag="div" className="text-center bg-white">
                <strong>{user.name}</strong>
              </DropdownItem>
              <NavLink to="/profile" className="text-dark text-decoration-none">
                <DropdownItem>
                  <i className="fa fa-user" />
                  <FormattedMessage id="layout.profile" />
                </DropdownItem>
              </NavLink>

              <NavLink to="/users" className="text-dark text-decoration-none" hidden={!user.admin}
                       disabled={!online}>
                <DropdownItem>
                  <i className="fa fa-users" />
                  <FormattedMessage id="layout.users" />
                </DropdownItem>
              </NavLink>
              <NavLink to="/settings" className={`text-dark text-decoration-none ${config && config.updates && config.updates.length > 0 ? 'updates-available' : ''}`}
                       hidden={!user.admin} disabled={!online}>
                <DropdownItem>
                  <i className="fa fa-cogs" />
                  <FormattedMessage id="layout.settings" />
                </DropdownItem>
              </NavLink>

              <NavLink to="/about" className="text-dark text-decoration-none">
                <DropdownItem>
                  <i className="fa fa-info-circle" />
                  <FormattedMessage id="layout.about" />
                </DropdownItem>
              </NavLink>

              <NavLink to="/help" className="text-dark text-decoration-none">
                <DropdownItem>
                  <i className="fa fa-question-circle" />
                  <FormattedMessage id="layout.help" />
                </DropdownItem>
              </NavLink>

              <DropdownItem onClick={event => onLogout(event)}>
                <i className="fa fa-lock"/>
                <FormattedMessage id="layout.logout" />
              </DropdownItem>
            </DropdownMenu>
          </UncontrolledDropdown>
        </Nav>
        <AppAsideToggler className={`aside-toggler d-md-down-none shadow-none ${syncing ? 'syncing' : ''}`}/>
        <AppAsideToggler className={`aside-toggler d-lg-none shadow-none ${syncing ? 'syncing' : ''}`} mobile />
      </React.Fragment>
    );
  }
}

NoteHeader.propTypes = propTypes;
NoteHeader.defaultProps = defaultProps;

export default inject('account', 'notes', 'application', 'profile')(
  observer(NoteHeader)
);
