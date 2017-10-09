import React from 'react';
import UserName from 'UserName';
import UserEmail from 'UserEmail';
import UserPhone from 'UserPhone';
import ClickIconPanel from 'ClickIconPanel';
import DeleteEditIconPanel from 'DeleteEditIconPanel';

const UserInfo = React.createClass({
    render(){
        let {id, names, emails, phones, subscribe, isCardEditDelete, isClickable, onClickable, onDelete,onEdit} = this.props;
        /**
         * Create handler function for render icon panel
         */
            let renderIconPanel = function () {

                if(isClickable) {
                    return <ClickIconPanel id = {id} onClickable = {onClickable}/>
                }else if (isCardEditDelete){
                    return <DeleteEditIconPanel id = {id} onDelete = {onDelete} onEdit = {onEdit}/>
                }
            };
        /**
         * Invoke handlers function
         */
        return (
            <div >
                {renderIconPanel()}
                <UserName names={names} subscribe={subscribe}/>
                <UserEmail emails={emails}/>
                <UserPhone phones={phones}/>
            </div>
        )
    }
});

export default UserInfo;