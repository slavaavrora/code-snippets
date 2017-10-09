/**
 * Stateless component for render user name
 * import react to connect with app
 * received needed @props from parent component
 * Using ES6 import
 */


import React from 'react';

const UserName = ({names, subscribe})=>{
    return(
        <div>
            <h3 className='name-info'>{names}</h3>
            <p className='subscribe-info'>{subscribe}</p>
        </div>
    )
};

/**
 * Using ES6 export
 */
export default UserName;