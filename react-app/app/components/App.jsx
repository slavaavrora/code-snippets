/**
 * Container component to render UserInfo, contain all busyness logic our app.
 *  We used to dummy data in user.json. It'll make easy to set for real life app data.
 *  import axios lib for easy data processing, and react for set up React lib
 */

import React from 'react';
import UserInfo from 'UserInfo';
import usersData from '../api/usersData';

const App = React.createClass({
    /**
     * Setting the Initial State
     * - pass empty array in user list
     * - while we haven't data from host add isLoading @param equal true
     */
    getInitialState(){
      return {
          usersList:[],
          isLoading: true
      }
    },
    /**
     * Setting the connect with data base and data processing used to promise
     * - switch state isLoading @param
     *
     */
    componentDidMount(){
        usersData.getUsers()
            .then(usersList => {
                this.setState({ usersList, isLoading:false });
            });
    },

    /**
     * Helpers function to log events
     * - Edit event
     * - Delete event
     * - Click event
     *
     */
    handleEdit(id){
        console.log(`Card ${id} will be Edit`);
    },
    handleDelete(id){
        console.log(`Card ${id} will be Delete`);
    },
    handleClickable(id){
        console.log(`Card ${id} will be Clickable`);
    },


      render(){
        let {usersList, isLoading} = this.state;
        console.info('this.state', usersList);
          console.log(this.props);
          let handleEdit =function (id) {
              console.log(`Card ${id} will be Edit`);
          };
          let handleDelete =function (id) {
              console.log(`Card ${id} will be Delete`);
          };
          let handleClickable =function (id) {
              console.log(`Card ${id} will be Clickable`);
          };
          /**
           * Create handler function for render users list
           * and pass to nested components necessary @props
           */
          function renderUsers() {

              if (usersList.length > 0){

                  if(isLoading){
                      return <h3 className='text-center'>Fetching users...</h3>;
                  }else if (usersList){
                      return usersList.map((userInfo)=>{

                          return(
                              <div key={userInfo.id} className='user-info-wrap'>
                                  <UserInfo
                                      key = {userInfo.id}
                                      id = {userInfo.id}
                                      names={userInfo.name}
                                      phones={userInfo.phones}
                                      emails={userInfo.emails}
                                      subscribe={userInfo.subscribe}
                                      isClickable={userInfo.isClickable}
                                      isCardEditDelete={userInfo.isCardEditDelete}
                                      onEdit = {handleEdit}
                                      onDelete = {handleDelete}
                                      onClickable = {handleClickable}

                                  />
                              </div>
                          )
                      })
                  }
              }else{
                  return <h3 className='text-center'>Nothing to render</h3>
              }
          }
          /**
           * Render Error message
           * If recieved bad data return Error message
           */
          function renderError() {
              if(typeof errorMessage === 'object'){
                  return (
                      <div> ERROR !!!</div>
                  )
              }
          }

          /**
           * Invoke handlers function
           */
          return (
              <div className='main-wrap-container'>
                  {renderUsers()}
                  {renderError()}
              </div>
          )
      }
});

export default App;