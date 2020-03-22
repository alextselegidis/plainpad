import React, { Component } from 'react';
import {HashRouter, Route, Switch} from 'react-router-dom';
// import { renderRoutes } from 'react-router-config';
import './App.scss';
import RecoverPassword from './views/Pages/RecoverPassword';

const loading = () => <div className="animated fadeIn pt-3 text-center">Loading...</div>;

// Containers
const NoteLayout = React.lazy(() => import('./containers/NoteLayout'));

// Pages
const Login = React.lazy(() => import('./views/Pages/Login'));
const Page404 = React.lazy(() => import('./views/Pages/Page404'));
const Page500 = React.lazy(() => import('./views/Pages/Page500'));

class App extends Component {
  render() {
    return (
      <HashRouter>
          <React.Suspense fallback={loading()}>
            <Switch>
              <Route exact path="/login" name="Login" render={props => <Login {...props}/>} />
              <Route exact path="/recover-password" name="Recover Password" render={props => <RecoverPassword {...props}/>} />
              <Route exact path="/404" name="Page 404" render={props => <Page404 {...props}/>} />
              <Route exact path="/500" name="Page 500" render={props => <Page500 {...props}/>} />
              <Route path="/" name="Home" render={props => <NoteLayout {...props}/>} />
            </Switch>
          </React.Suspense>
      </HashRouter>
    );
  }
}

export default App;
