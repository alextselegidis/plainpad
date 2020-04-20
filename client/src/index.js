import 'react-app-polyfill/ie9'; // For IE 9-11 support
import 'react-app-polyfill/stable';
// import 'react-app-polyfill/ie11'; // For IE 11 support
import './polyfill'
import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import App from './App';
import * as serviceWorker from './serviceWorker';
import {Provider} from 'mobx-react';
import applicationStore from './stores/ApplicationStore';
import accountStore from './stores/AccountStore';
import notesStore from './stores/NotesStore';
import usersStore from './stores/UsersStore';
import settingsStore from './stores/SettingsStore';
import {IntlProvider} from 'react-intl';
import {messages} from './lang';

applicationStore.initialize();

const locale = accountStore.user ? accountStore.user.locale : 'en-US';

const Root = () => {
  return (
    <Provider
      applicationStore={applicationStore}
      usersStore={usersStore}
      settingsStore={settingsStore}
      notesStore={notesStore}
      accountStore={accountStore}
    >
      <IntlProvider locale={locale} messages={messages[locale]}>
       <App/>
      </IntlProvider>
    </Provider>
  );
};

ReactDOM.render(<Root/>, document.getElementById('root'));

// If you want your app to work offline and load faster, you can change
// unregister() to register() below. Note this comes with some pitfalls.
// Learn more about service workers: http://bit.ly/CRA-PWA
serviceWorker.register({});
