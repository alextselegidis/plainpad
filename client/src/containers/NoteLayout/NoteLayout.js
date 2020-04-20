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

import React, { Component, Suspense } from 'react';
import { Redirect, Route, Switch } from 'react-router-dom';
import * as router from 'react-router-dom';
import {Button,  Container, Input, InputGroup, InputGroupAddon, InputGroupText} from 'reactstrap';

import {
  AppHeader,
  AppSidebar,
  AppSidebarFooter,
  AppSidebarForm,
  AppSidebarHeader,
  AppSidebarNav2 as AppSidebarNav, AppAside,
} from '@coreui/react';
import routes from '../../routes';
import NoteAside from './NoteAside';
import {inject, observer} from 'mobx-react';
import {translate} from '../../lang';
import NoteCopyright from './NoteCopyright';

const NoteHeader = React.lazy(() => import('./NoteHeader'));

class NoteLayout extends Component {
  loading = () => <div className="animated fadeIn pt-1 text-center">Loading...</div>;

  onMainClick() {
    if (document.body.classList.contains('aside-menu-show')) {
      document.body.classList.remove('aside-menu-show');
    }
  }

  render() {
    const {
      account,
      application,
      notes,
      history
    } = this.props;

    const {
      user
    } = account;

    if (!user) {
      return <Redirect to="/login" />;
    }

    const {
      online
    } = application;

    const {
      noteList,
      filter
    } = notes;

    const nav = {
      items: noteList.map((note) => {
        return {
          name: user.view === 'compact' ? note.title : note.content.substring(0, 100).replace("\n", '').trim(),
          url: '/notes/' + note.id,
          icon: note.pinned ? 'fa fa-thumb-tack' : 'd-none',
        };
      })
    };

    return (
      <div className={`app ${user.theme === 'dark' ? 'dark' : ''}`}>
        <AppHeader fixed>
          <Suspense  fallback={this.loading()}>
            <NoteHeader onLogout={(event) => {
              event.preventDefault();
              account.logout();
              history.push('/login');
            }}/>
          </Suspense>
        </AppHeader>
        <div className="app-body">
          <AppSidebar className="border-right" fixed display="lg">
            <AppSidebarHeader />
            <AppSidebarForm />

            <InputGroup className="border-bottom">
              <InputGroupAddon addonType="prepend" className="rounded-0">
                <InputGroupText className="filter-addon-content rounded-0 border-0 pr-0">
                  <i className="fa fa-search" />
                </InputGroupText>
              </InputGroupAddon>

              <Input value={filter} className="filter rounded-0 border-0 shadow-none pl-2"
                     placeholder={translate('notes.filter').toUpperCase()}
                     onChange={(event) => notes.updateFilter(event.target.value)} />

              <InputGroupAddon addonType="prepend" className="rounded-0 clear-filter" hidden={!filter.length}
                               onClick={() => notes.updateFilter('')}>
                <InputGroupText className="filter-addon-content rounded-0 border-0">
                  <i className="fa fa-times" />
                </InputGroupText>
              </InputGroupAddon>
            </InputGroup>

            <Button color="primary" outline block className="new-note text-muted text-left rounded-0 border-top-0 border-left-0 border-right-0 border-bottom"
                    onClick={() => notes.add()}>
              <i className="fa fa-plus mr-2 ml-1" />
              {translate('notes.newNote').toUpperCase()}
            </Button>

            <Suspense>
            <AppSidebarNav navConfig={nav} {...this.props} router={router}/>
            </Suspense>
            <AppSidebarFooter>
              <NoteCopyright />
            </AppSidebarFooter>
          </AppSidebar>
          <main className="main" onClick={this.onMainClick.bind(this)}>
            <Container fluid className="h-100">
              <Suspense fallback={this.loading()}>
                <Switch>
                  {routes.map((route, idx) => {
                    return route.component ? (
                      <Route
                        key={idx}
                        path={route.path}
                        exact={route.exact}
                        name={route.name}
                        render={props => (
                          <route.component {...props} />
                        )} />
                    ) : (null);
                  })}
                  <Redirect from="/" to="/login" />
                </Switch>
              </Suspense>
            </Container>
          </main>
          <AppAside fixed>
            <Suspense fallback={this.loading()}>
              <NoteAside />
            </Suspense>
          </AppAside>

          <div className="offline-mode" hidden={online}>
            Offline
          </div>
        </div>
      </div>
    );
  }
}

export default inject('account', 'notes', 'application')(
  observer(NoteLayout)
);
