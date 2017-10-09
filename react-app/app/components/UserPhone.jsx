/**
 * Stateless component for render user phones
 * import react to connect with app
 * received needed @props from parent component
 * Using ES6 import
 */

import React from 'react';

const UserPhone = ({phones})=>{
    /**
     * Create handler function for render phones list
     */
    let renderPhones = function () {
        if(Array.isArray(phones)){
            if(phones.length > 0){
                return phones.map((phoneNumber, index)=>{
                    return (
                            <li className="phones-info" key={index}><i className="zmdi zmdi-comment-outline"></i><span>{phoneNumber}</span> </li>
                    )
                });
            } else{
                return <div>noting render</div>
            }
        }
    };
    /**
     * Invoke handlers function
     */
    return(
        <div>
            <ul>
                {renderPhones()}
            </ul>
        </div>
    )
};

/**
 * Using ES6 export
 */
export default UserPhone;