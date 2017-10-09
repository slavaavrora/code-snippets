/**
 * Root component our app
 * ES6 import react, react-dom for create react app
 * import main container component App
 */

import React from 'react';
import ReactDOM from 'react-dom';
import App from './components/App';

//App css
require('style!css!sass!applicationStyles');

ReactDOM.render(
    <App />,
    document.getElementById('app')
);