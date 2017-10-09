'use strict';

/**
 * Service for single order
 */
window.app.service('OrdersSingle', [
    '$http', '$q', '$injector', '$ocLazyLoad', 'spinner', 'notifications',
    function($http, $q, $injector, $ocLazyLoad, spinner, notifications)
    {
        let statusColors = {
            PENDING_DOCUMENTS: 'blue',
            INPROGRESS: 'blue',
            INFO_REQUIRED: 'orange',
            FINALISED: 'green',
            COMPLETED: 'green',
            FORGERY: 'red',
            REFUNDED: 'orange',
            CANCELLED: 'red',
            CHARGEBACK: 'orange'
        };


        /**
         * Loads payment service and shows Stripe payment modal form
         *
         * @param {String} paymentKey Payment system publish key
         * @param {Number} amount     Amount in cents
         * @param {String} currency   Payment currency
         * @param {String} [orgID]    Organization UUID
         * @return {Promise}
         */
        this.paymentForm = (paymentKey, amount, currency, orgID) => $q((resolve, reject) =>
        {
            let spinnerClose = spinner(document.body);
            $ocLazyLoad.load(window.DGY.getServiceUri('Payment')).then(() =>
            {
                $injector.get('Payment').stripe(paymentKey, amount, currency, orgID).then(resolve, reject);
            }, () =>
            {
                notifications.error('notifications.getPaymentServiceError.title');
                reject();
            }).then(spinnerClose);
        });

        /**
         * Pays order by ID
         *
         * @param {String} orderID      Order UUID
         * @param {Number} amount       Amount in cents
         * @param {String} currency     Payment currency
         * @param {String} [paymentKey] Payment system publish key. Will be got from server by orderID if it's not passed
         * @param {String} [orgID]      Organization UUID. Will be got from server together with paymentKey if paymentKey is not passed
         */
        this.pay = (orderID, amount, currency, paymentKey, orgID) => $q((resolve, reject) =>
        {
            function error()
            {
                notifications.error('orders.single.notifications.payOrderError.title');
                reject();
            }

            $q((keyResolve, keyReject) =>
            {
                if (paymentKey) {
                    return keyResolve({
                        key  : paymentKey,
                        orgID: orgID
                    });
                }

                let spinnerClose = spinner(document.body);
                $http.get('/api/orders/v1/paymentconfiguration/' + orderID).then(r => keyResolve({
                    key  : r.data.stripe_pub_key,
                    orgID: r.data.org_id
                }), keyReject).then(spinnerClose);
            }).then(data =>
            {
                this.paymentForm(data.key, amount, currency, data.orgID).then(payData =>
                {
                    let spinnerClose = spinner(document.body);
                    $http.put('/api/orders/v1/charge/' + orderID, {
                        payment_params: {
                            source: payData.token
                        }
                    }).then(() =>
                    {
                        notifications.success('orders.single.notifications.payOrderSuccess.title');
                        resolve();
                    }, error).then(spinnerClose);
                }, reject);
            }, error);
        });

        /**
         * Returns color for status
         *
         * @param {String} status
         * @return {String}
         */
        this.getStatusColor = status => statusColors[status];

        /**
         * Deletes order by id
         *
         * @param {String} id Order UUID
         * @return {Promise}
         */
        this.delete = id => $q((resolve, reject) =>
        {
            $http.delete('/api/orders/v1/orders/' + id).then(() =>
            {
                notifications.success('orders.single.notifications.deleteOrderSuccess.title');
                resolve();
            }, () =>
            {
                notifications.error('orders.single.notifications.deleteOrderError.title');
                reject();
            });
        });

        /**
         * Loads order attachment file
         *
         * @param {String} orderID     Order UUID
         * @param {String} processorID Processor public id
         * @param {File}   file        File object
         * @return {Promise}
         */
        this.loadAttachment = (orderID, processorID, file) => $q((resolve, reject) =>
        {
            let fileData = new FormData;
            fileData.append('filename', file);

            $http.put(`/api/orders/v1/orders/${orderID}/processor/${processorID}/attachment/data/pdf?filename=${file.name}`, fileData, {
                headers: {
                    'Content-Type' : file.type
                }
            }).then(resolve, () =>
            {
                notifications.warning(['orders.single.notifications.attachmentUploadError.title', {filename: file.name}]);
                reject();
            });
        });
    }
]);