/**
 * Stateless component for render user emails
 * import react to connect with app
 * received needed @props from parent component
 * Using ES6 import
 */

import React from 'react';

const UserEmail = ({emails})=>{
    /**
     * Create handler function for render emails list
     */
    let renderEmails = function () {
        if(Array.isArray(emails)){
            if(emails.length > 0){
                return emails.map((email, i)=>{
                    return (
                           <li key={i}><i className="zmdi zmdi-email" ></i><span >{email}</span> </li>
                    )
                });
            } else{
                return <div>noting render</div>
            }
        }
    };
    return(
        <div >
            <ul className="email-info" >
                {renderEmails()}
            </ul>
        </div>
    )
};
/**
 * Using ES6 export
 */
export default UserEmail;