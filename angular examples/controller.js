'use strict';

/**
 * Controller for single order page
 */
window.app.controller('OrdersSingleCtrl', [
    '$scope', '$http', '$state', '$stateParams', '$element', '$filter', '$translate', 'spinner', 'notifications', 'OrdersSingle',
    ($scope, $http, $state, $stateParams, $element, $filter, $translate, spinner, notifications, OrdersSingle) =>
    {
        /**
         * Loads order and order's events
         */
        function loadOrder()
        {
            let spinnerClose = spinner($element[0]);
            $http.get('/api/orders/v1/orders/' + $stateParams.id).then(r =>
            {
                $scope.order = r.data;

                /**
                 * Loads order events
                 */
                $http.get('/api/orders/v1/events/' + $stateParams.id).then(r =>
                {
                    let dateFiler = $filter('date');
                    $scope.events = r.data.events.map(item =>
                    {
                        item.displayed_type = $translate.instant('orders.events.' + item.event_type + '.title');
                        item.description = $translate.instant('orders.events.' + item.event_type + '.description', item);
                        item.event_date = new Date(item.event_date);
                        item.formatted_date = dateFiler(item.event_date, $scope.dateTimeFormat);

                        return item;
                    });
                }, () =>
                {
                    notifications.error('orders.single.notifications.getOrderEventsError.title');
                });

                /**
                 * Prepare academic history data for view
                 */
                $scope.order.educational_history.forEach(item =>
                {
                    item.year_to = item.current_course ? (item.year_to || '-') : $translate.instant('orders.single.academicHistory.currently');
                    item.displayed_course = item.award_received || item.current_course;
                });
            }, () =>
            {
                notifications.error('orders.single.notifications.getOrderError.title');
                $state.go('orders.list');
            }).then(spinnerClose);
        }


        /**
         * Attachments dropzone
         */
        $scope.attachmentsDropzone = [];
        $scope.attachments = [];
        $scope.$watch('attachmentsDropzone.length', () =>
        {
            /**
             * Filter duplicates
             */
            $scope.attachmentsDropzone = $scope.attachmentsDropzone.filter(item =>
            {
                for (let i = $scope.attachments.length; i--; ) {
                    if ($scope.attachments[i] === item.name) {
                        return false;
                    }
                }

                return true;
            });

            /**
             * Check files amount for uploading
             */
            let files = $scope.attachmentsDropzone.length;
            if (!files) {
                return;
            }

            let spinnerClose = spinner($element[0]);

            function loaded()
            {
                if (!--files) {
                    $scope.attachmentsDropzone = [];
                    spinnerClose();
                }
            }

            /**
             * Load files
             */
            $scope.attachmentsDropzone.forEach(item =>
            {
                OrdersSingle.loadAttachment($scope.order.id, $scope.order.destination.institution_id, item).then(() =>
                {
                    $scope.attachments.push(item.name);
                    loaded();
                }, loaded);
            });
        });


        $scope.actions = {
            /**
             * Returns color for status
             *
             * @param {String} status
             * @return {String}
             */
            getStatusColor: OrdersSingle.getStatusColor,

            /**
             * Deletes order and forwards to order list page
             */
            delete: () => {
                let spinnerClose = spinner($element[0]);
                OrdersSingle.delete($scope.order.id).then(() => $state.go('orders.list'), spinnerClose);
            },

            /**
             * Pays order
             */
            pay: () =>
            {
                OrdersSingle.pay($scope.order.id, $scope.order.fee.amount, $scope.order.fee.currency).then(loadOrder);
            }
        };

        /**
         * Init page
         */
        loadOrder();
    }
]);