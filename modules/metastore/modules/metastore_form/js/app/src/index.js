import React from 'react';
import ReactDOM from 'react-dom';
import App from './App';
import * as serviceWorker from './serviceWorker';
import { BrowserRouter as Router} from "react-router-dom";

// const uuid = window && window.drupalSettings.tempUUID ? window.drupalSettings.tempUUID : '123';
// const isNew = window && window.drupalSettings.isNew ? window.drupalSettings.isNew : 1;
const uuid = '1f2042ad';
const isNew = 1;

ReactDOM.render(<Router><App tempUUID={uuid} isNew={isNew} /></Router>, document.getElementById('app'));

// If you want your app to work offline and load faster, you can change
// unregister() to register() below. Note this comes with some pitfalls.
// Learn more about service workers: https://bit.ly/CRA-PWA
serviceWorker.register();
