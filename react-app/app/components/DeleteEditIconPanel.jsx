import React from 'react';

const DeleteEditIconPanel = React.createClass({
    render(){
        let {id}=this.props;

        return(
            <div className='icons-edit-delete'>
                <div onClick={()=>{this.props.onEdit(id)}}>
                    <i className="zmdi zmdi-edit"></i>
                </div>
                <div onClick={()=>{this.props.onDelete(id)}}>
                    <i className="zmdi zmdi-delete"></i>
                </div>
            </div>
        )
    }
});

export default DeleteEditIconPanel;