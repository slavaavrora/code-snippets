'use strict';

/**
 * Controller for profile settings page
 *
 * @author <sanych.zp@gmail.com> Alex Krupko
 * @author <krasnyansky.v@gmail.com> Slava Krasnyansky
 * @date 07 Dec 2016
 *
 * @version 23 Jun 2017
 */
window.app.controller('ProfileSettingsCtrl', [
    '$scope', '$element', '$http', '$translate', '$state', '$q', 'modalWindow', 'notifications', 'spinner',
    ($scope, $element, $http, $translate, $state, $q, modalWindow, notifications, spinner) =>
    {
        let primaryEmail            = '',
            primaryEmailVerified    = false,
            shibbolethOrganizations = [];

        /**
         * Gets and prepares emails list
         *
         * @return {Promise} Http promise
         */
        let loadEmails = () => $http.get('/accounts/emails').then(r =>
        {
            $scope.emails = r.data.emails.map(item =>
            {
                if (item.primary) {
                    primaryEmail = item.email;
                    item.verified && (primaryEmailVerified = true);
                    item.status = 'Primary';
                } else {
                    item.status = item.verified ? 'Verified' : 'NotVerified';
                }

                item.displayedStatus = $translate.instant('profile.settings.statuses.' + item.status);

                return item;
            });
        });

        /**
         * Gets and prepares social network providers list (also shibboleth data)
         *
         * @return {Promise} Http promise
         */
        let loadProviders = () => $q((resolve, reject) =>
        {
            $http.get('/accounts/linkedaccount').then(r =>
            {
                $scope.userProviders = r.data.providers;
                $scope.shibbolethAccounts = r.data.shibbolethAccounts;

                let providers = [
                    {
                        id     : 'google',
                        name   : 'Google+',
                        classes: 'googleplus icon icon-google-plus'
                    },
                    {
                        id     : 'linkedin',
                        name   : 'LinkedIn',
                        classes: 'linkedin icon icon-linkedin'
                    },
                    {
                        id     : 'facebook',
                        name   : 'Facebook',
                        classes: 'facebook icon icon-facebook'
                    }
                ];

                $scope.providers = providers.map(item =>
                {
                    item.status = r.data.providers.indexOf(item.id) < 0 ? 'NotLinked' : 'Linked';
                    item.displayedStatus = $translate.instant('profile.settings.statuses.' + item.status);

                    return item;
                });

                /**
                 * Load shibboleth required data if this feature is enabled
                 */
                if (!$scope.app.features.shibboleth) {
                    return resolve();
                }

                $scope.user.shibboleth.getItems().then(data =>
                {
                    shibbolethOrganizations = data;

                    let namesByEntityID = {};
                    shibbolethOrganizations.forEach(item => namesByEntityID[item.id] = item.name);

                    $scope.shibbolethAccounts.forEach(item =>
                    {
                        item.type = 'shibboleth';
                        item.name = namesByEntityID[item.identityProvider]
                            || ($translate.instant('profile.settings.university') + ' (' + item.identityProvider + ')');

                        for (let i = $scope.shibbolethAccounts.length; i--; ) {
                            if ($scope.shibbolethAccounts[i] !== item
                                && $scope.shibbolethAccounts[i].identityProvider === item.identityProvider
                            ) {
                                item.name += ` (${item.accountId})`;
                                break;
                            }
                        }
                    });

                    resolve();
                }, reject);
            }, reject);
        });

        /**
         * Loads page data
         */
        let spinnerClose = spinner($element[0]);
        $q.all([loadEmails(), loadProviders()]).then(spinnerClose, () =>
        {
            notifications.error('notifications.dataLoadError.title', 'notifications.dataLoadError.description');
            $state.go('home');
        });


        $scope.actions = {
            /**
             * Change (set) profile password modal window
             *
             * @param {Boolean} [isSet=false] If true will be shown modal for setting password
             */
            changePassword: isSet =>
            {
                if (!primaryEmailVerified) {
                    return notifications.warning(
                        ['profile.settings.notifications.primaryEmailNotVerified.title', {email: primaryEmail}],
                        'profile.settings.notifications.primaryEmailNotVerified.description'
                    );
                }

                let modalScope = $scope.$new(true);
                modalScope.data = {};
                modalScope.isSet = isSet;
                modalScope.passwordCompare = form =>
                {
                    form.repeatPassword.$setValidity('equal', form.repeatPassword.$modelValue === form.password.$modelValue);
                };

                modalWindow(
                    isSet ? 'modals.setPassword.title' : 'modals.changePassword.title',
                    'changePassword',
                    modalScope,
                    'modals.changePassword.submit'
                )((modalClose, modalForm) =>
                {
                    let spinnerClose = spinner(modalForm);
                    $scope.user[isSet ? 'setPassword' : 'changePassword'](modalScope.data).then(() =>
                    {
                        $scope.userProviders.push('password');
                        modalClose();
                    }, spinnerClose);
                });
            },

            /**
             * Links new email address to user account
             */
            addEmail: () =>
            {
                if (!primaryEmailVerified) {
                    return notifications.warning(
                        ['profile.settings.notifications.primaryEmailNotVerified.title', {email: primaryEmail}],
                        'profile.settings.notifications.primaryEmailNotVerified.description'
                    );
                }

                let modalScope = $scope.$new(true);
                modalWindow('modals.linkEmail.title', 'linkEmail', modalScope, 'modals.linkEmail.submit')((modalClose, modalWindow) =>
                {
                    let spinnerClose = spinner(modalWindow);
                    $scope.user.linkEmail(modalScope.email).then(() =>
                    {
                        loadEmails().finally(modalClose);
                    }, spinnerClose);
                });
            },

            /**
             * Opens modal window for editing first name and last name of current user
             *
             * TODO: don't delete!!! It will be used in future
             */
            editFullName: () =>
            {
                let modalScope = $scope.$new(true);
                modalScope.firstName = $scope.user.data.first_name;
                modalScope.lastName = $scope.user.data.last_name;

                modalWindow('modals.editFullName.title', 'editFullName', modalScope)((modalClose, modalWindow) =>
                {
                    let spinnerClose = spinner(modalWindow);
                    $scope.user.editFullName(modalScope.firstName, modalScope.lastName).then(modalClose, spinnerClose);
                });
            },

            /**
             * Sends email with verification link
             *
             * @param {Object} item
             * @param {Object} $event
             */
            verify: (item, $event) =>
            {
                let closeSpinner = spinner($event.target.parent('.actions'));
                $http.post(`/accounts/emails/${item.email}/verificationservice`).then(() =>
                {
                    notifications.success(
                        ['profile.settings.notifications.verifyEmailSuccess.title', item],
                        'profile.settings.notifications.verifyEmailSuccess.description'
                    );
                }, () =>
                {
                    notifications.error(['profile.settings.notifications.verifyEmailError.title', item]);
                }).then(closeSpinner);
            },

            /**
             * Removes email from profile
             *
             * @param {Object} item
             */
            removeEmail: item =>
            {
                modalWindow.confirm(
                    'profile.settings.modalEmailRemove.title',
                    ['profile.settings.modalEmailRemove.message', item],
                    'profile.settings.modalEmailRemove.submit'
                )((modalClose, modalForm) =>
                {
                    let spinnerClose = spinner(modalForm);
                    $http.delete(`/users/${$scope.user.data.id}/emails/${item.id}`).then(() =>
                    {
                        notifications.success(['profile.settings.notifications.removeEmailSuccess.title', item]);
                        modalClose();

                        let spinnerClose = spinner($element[0]);
                        loadEmails().finally(spinnerClose);
                    }, () =>
                    {
                        notifications.error(['profile.settings.notifications.removeEmailError.title', item]);
                    }).then(spinnerClose);
                });
            },

            /**
             * Adds social network provider
             *
             * @param {Object} item
             */
            addProvider: item =>
            {
                location.href = '/authenticate/' + item.id;
            },

            /**
             * Removes social network or shibboleth provider form profile
             *
             * @param {Object} item
             */
            removeProvider: item =>
            {
                modalWindow.confirm(
                    'profile.settings.modalProviderRemove.title',
                    ['profile.settings.modalProviderRemove.message', item],
                    'profile.settings.modalProviderRemove.submit'
                )((modalClose, modalForm) =>
                {
                    let spinnerClose = spinner(modalForm);
                    $http.delete(
                        item.type === 'shibboleth'
                            ? '/accounts/' + item.id
                            : `/users/${$scope.user.data.id}/providers/${item.id}`
                    ).then(() =>
                    {
                        notifications.success(['profile.settings.notifications.removeProviderSuccess.title', item]);
                        modalClose();

                        let spinnerClose = spinner($element[0]);
                        loadProviders().finally(spinnerClose);
                        $scope.user.initializeUser();
                    }, () =>
                    {
                        notifications.error(['profile.settings.notifications.removeProviderError.title', item]);
                    }).then(spinnerClose);
                });
            },

            /**
             * Sets email as primary
             *
             * @param {Object} item
             */
            setPrimary: item =>
            {
                let closeSpinner = spinner($element[0]);
                $http.put(`/users/${$scope.user.data.id}/primaryemail`, {emailId: item.id}).then(() =>
                {
                    notifications.success(['profile.settings.notifications.setEmailPrimarySuccess.title', item]);

                    let spinnerClose = spinner($element[0]);
                    loadEmails().finally(spinnerClose);
                }, () =>
                {
                    notifications.error(['profile.settings.notifications.setEmailPrimaryError.title', item]);
                    closeSpinner();
                });
            },

            /**
             * Returns color for status
             *
             * @param {String} status
             * @return {String}
             */
            getStatusColor: status =>
            {
                return {
                    Primary    : 'blue',
                    Verified   : 'green',
                    NotVerified: 'red',
                    Linked     : 'green',
                    NotLinked  : 'red'
                }[status];
            },

            /**
             * Opens modal window for selecting organization
             */
            addShibboleth: () =>
            {
                let modalScope = $scope.$new(true);

                /**
                 * It's fire when organization name field was changed
                 */
                modalScope.organizationChanged = () =>
                {
                    if (!modalScope.organizationName) {
                        return modalScope.organizations = shibbolethOrganizations;
                    }

                    let results = [],
                        regexp  = new RegExp(modalScope.organizationName, 'i');
                    shibbolethOrganizations.forEach(item => regexp.test(item.name) && results.push(item));
                    modalScope.organizations = results;
                };

                /**
                 * It's fire when user selects item in dropdown list
                 *
                 * @param {Object} organization
                 * @param {Event}  event
                 */
                modalScope.selectOrganization = (organization, event) =>
                {
                    event.preventDefault();
                    modalScope.selectedOrganization = organization;
                    modalScope.organizationName = organization.name;
                    modalScope.organizationChanged();
                };

                /**
                 * Organization name field loses focus
                 */
                modalScope.organizationBlur = () =>
                {
                    modalScope.organizationName = modalScope.organizationName && modalScope.selectedOrganization
                        ? modalScope.selectedOrganization.name
                        : null;
                };

                /**
                 * Returns link for organization
                 *
                 * @param {Object} organization
                 * @return {String}
                 */
                modalScope.getLink = organization => window.location.origin + '/Shibboleth.sso/Login?SAMLDS=1&target='
                    + window.location.origin + '/authenticate/shibboleth&entityID=' + organization.id;


                modalWindow('modals.linkShibboleth.title', 'linkShibboleth', modalScope, 'modals.linkShibboleth.submit')(
                    (modalClose) =>
                    {
                        if (modalScope.selectedOrganization) {
                            modalClose(); // for Mozilla browser
                            window.location = modalScope.getLink(modalScope.selectedOrganization);
                        }
                    }, null, modalScope.organizationChanged
                );
            }
        };
    }
]);
