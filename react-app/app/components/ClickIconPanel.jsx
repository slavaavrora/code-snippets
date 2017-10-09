/**
 * Stateless component for render click icon
 * import react to connect with app
 * received needed @props from parent component
 * Using ES6 import
 */

import React from 'react';

const ClickIconPanel = React.createClass({

    render(){
        let {id} = this.props;

        return(
            <div className="icon-clicable" onClick={()=>{this.props.onClickable(id)}}>
                <i className="zmdi zmdi-chevron-right"></i>
            </div>
        )
    }
});


/**
 * Using ES6 export
 */
export default ClickIconPanel;