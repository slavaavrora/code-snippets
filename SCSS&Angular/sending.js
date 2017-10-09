'use strict';

/**
 * Controller for sending documents page
 *
 * @author <sanych.zp@gmail.com> Alex Krupko
 * @author <krasnyansky.v@gmail.com> Slava Krasnyansky
 * @date 11 Jan 2016
 */

window.app.controller('DocumentsSendingCtrl', [
    '$scope', '$rootScope', '$element', '$state', '$http', '$translate', 'spinner', 'notifications',
    ($scope, $rootScope, $element, $state, $http, $translate, spinner, notifications) =>
    {
        let spinnerClose = spinner($element[0]);

        /**
         * Check user authorization
         */
        if (!$scope.user.isAuthorized) {
            $rootScope.documentSendingInited = true;
            return $state.go('user.login');
        }

        /**
         * Try to get CSRF token for POST requests
         */
        let csrfToken = document.cookie.match(/(?:^|\b)csrf-token=([\da-f-]+)(?:$|;)/);
        if (csrfToken) {
            csrfToken = decodeURIComponent(csrfToken[1]);
        } else {
            notifications.error('notifications.dataLoadError.title', 'notifications.dataLoadError.description');
            $state.go('home');
            return;
        }

        /**
         * Groups documents by organizations
         *
         * @param {Array} documents
         * @return {Array}
         */
        function groupDocumentsByOrganization(documents)
        {
            let organizations = [];
            documents.forEach(doc =>
            {
                for (let i = organizations.length; i--; ) {
                    if (organizations[i].id === doc.org_id) {
                        return organizations[i].documents.push(doc);
                    }
                }

                organizations.push({
                    documents : [doc],
                    id        : doc.org_id,
                    name      : doc.org_name
                });
            });

            return organizations;
        }

        /**
         * Creates and sends by POST method hidden form for data
         *
         * @param {Object} data
         */
        function sendPostRequest(data)
        {
            data.csrfToken = csrfToken;

            let form = document.createElement('form');
            form.setAttribute('action', '/authorize/do');
            form.setAttribute('method', 'post');

            /**
             * Creates new field and adds them to form
             *
             * @param {String} name
             * @param {String|Number} value
             */
            function addField(name, value)
            {
                let field = document.createElement('input');
                field.setAttribute('name', name);
                field.setAttribute('value', value);
                field.setAttribute('type', 'hidden');
                form.appendChild(field);
            }

            /**
             * Recursive function for data preparing
             *
             * @param {Object} data
             * @param {String} [keyPrefix]
             */
            function prepareParams(data, keyPrefix = '')
            {
                for (let key in data) {
                    if (!data.hasOwnProperty(key)) {
                        continue;
                    } else if (Array.isArray(data[key])) {
                        data[key].forEach((item, index) =>
                        {
                            Array.isArray(item) || typeof item === 'object'
                                ? prepareParams(item, (keyPrefix ? `${keyPrefix}.` : '') + `${key}[${index}]`)
                                : addField((keyPrefix ? `${keyPrefix}.` : '') + `${key}[${index}]`, item);
                        });
                    } else if (typeof data[key] === 'object') {
                        prepareParams(data[key], keyPrefix ? `${keyPrefix}.${key}` : key)
                    } else {
                        addField(keyPrefix ? `${keyPrefix}.${key}` : key, data[key]);
                    }
                }
            }

            prepareParams(data);
            document.body.appendChild(form);
            form.submit();
        }

        /**
         * Number of selected documents
         */
        $scope.selectedDocuments = 0;

        /**
         * Flag which shows review tab state
         */
        $scope.reviewTabActive = false;


        $scope.actions = {
            /**
             * Cancel button handler function
             */
            cancel: sendPostRequest.bind(null, {permission: 'DENY'}),

            /**
             * Submit button handler function
             */
            submit: () =>
            {
                let data = {permission: 'ALLOW', groups: []};
                $scope.groups.forEach(item =>
                {
                    item.documents.length && data.groups.push({
                        id         : item.id,
                        documentIds: item.documents
                    });
                });
                sendPostRequest(data);
            },

            /**
             * Adds or removes document to/from group
             *
             * @param {Object} document
             * @param {Object} group
             */
            toggleDocument: (document, group) =>
            {
                let index = group.documents.indexOf(document.id);
                if (index >= 0) {
                    group.documents.splice(index, 1);
                    --$scope.selectedDocuments;
                } else {
                    group.documents.push(document.id);
                    ++$scope.selectedDocuments;
                }
            },

            /**
             * Sets passed group as active
             *
             * @param {Object} group
             */
            activeGroup: group =>
            {
                $scope.reviewTabActive = false;
                $scope.activeGroup = group;
            },

            /**
             * Sets review tab as active
             */
            review: () =>
            {
                $scope.reviewTabActive = true;
            }
        };

        /**
         * Get request metadata
         */
        $http.get('/authorize/metadata').then(r =>
        {
            let params = {
                format: 'full',
                issuinginstitution: r.data.scope === 'all' ? null : r.data.scope,
                'status[]': 'available',
                'sort[issued_date]': 'desc',
            };

            $http.get('/api/documents/v1/documents', {params, cache: true}).then(r =>
            {
                if (!r.data.count) {
                    return sendPostRequest({permission: 'EMPTY_DOCUMENT'});
                }

                $scope.documentNames = [];
                r.data.documents.forEach(item => $scope.documentNames[item.id] = item.name);

                $scope.organizations = groupDocumentsByOrganization(r.data.documents);
                spinnerClose();
            }, sendPostRequest.bind(null, {permission: 'UNAUTHORIZED'}) );

            /**
             * Client name
             */
            $scope.clientName = r.data.client_name;

            /**
             * Prepare groups
             */
            $scope.groups = [];
            if (r.data.groups && Array.isArray(r.data.groups)) {
                $scope.groups = r.data.groups;
                $scope.groups.forEach(item => item.documents = []);
            }

            if (!$scope.groups.length) {
                $scope.withoutGroups = true;
                $scope.groups = [{id: -1, documents: [], label_1: $translate.instant('documents.sending.documents')}];
            }

            for (let i = $scope.groups.length; i--;) {
                $scope.groups[i].prev = $scope.groups[i - 1] || null;
                $scope.groups[i].next = $scope.groups[i + 1] || null;
            }

            $scope.activeGroup = $scope.groups[0];
        }, () =>
        {
            notifications.error('notifications.dataLoadError.title', 'notifications.dataLoadError.description');
            $state.go('home');
        });
    }
]);