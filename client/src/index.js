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
import {IntlProvider} from 'react-intl';
import {messages} from './lang';
import stores from './stores';

stores.application.initialize();

const locale = stores.account.user ? stores.account.user.locale : 'en-US';

const Root = () => {
  return (
    <Provider {...stores}>
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
