/**
 *  Helper function for get data from hosting
 *  We used to dummy data in user.json. It'll make easy to set for real life app data.
 *  import axios lib for easy data processing, and react for set up React lib
 */
let axios = require('axios');

const BASE_URL = 'users.json';

 export default {
    getUsers() {
        return axios.get(BASE_URL).then(function (res, rej) {
                return res.data;
        },function () {
            throw new Error();
        });
    }
};
