jQuery(document).ready(function($) {

    var $resetPassFormContainer = $('#reset-form'),
        $recoverPassFormContainer = $('#recover-form'),
        popups,
        magnificPopupConfig;

    /* > POPUPS CONFIG */
    //=================================================================
    popups = ['auth', 'reg', 'recover', 'reset'];
    magnificPopupConfig = {};

    for (var i = 0; i < popups.length; i++) {
        magnificPopupConfig[popups[i]] = {
            items: {
                src: '#' + popups[i] +'-form', type: 'inline'
            }
            , closeMarkup: '<span class="mfp-close icon-close"></span>'
            , fixedContentPos: true
            , preloader: false
            , callbacks: {
                beforeOpen: function() {
                    this.st.mainClass = 'mfp-zoom-in';
                }
            }
            , midClick: true
        }
    }
    /* < POPUPS CONFIG */

    if (location.search.match(/auth=1/) !== null && !$('body').hasClass('logged-in')) {
        $.magnificPopup.open(magnificPopupConfig['auth']);
    }

    if (location.search.match(/action=register/) !== null && !$('body').hasClass('logged-in')) {
        $.magnificPopup.open(magnificPopupConfig['reg']);
    }

    if (location.search.match(/action=lostpassword/) !== null && !$('body').hasClass('logged-in')) {
        $.magnificPopup.open(magnificPopupConfig['recover']);
    }

    if ($resetPassFormContainer.length > 0) {
        $.magnificPopup.open(magnificPopupConfig['reset']);
    }
    if ($recoverPassFormContainer.length > 0) {
        $('.password-recovery').magnificPopup(magnificPopupConfig['recover']);
    }

    window.bmrAuth.show = function(type, text) {
        text = text || '';
        if (type === 'auth' && !document.body.classList.contains('logged-in')) {
            var summary = document.querySelector('#loginform .summary');
            summary && text.length && (summary.innerHTML = text) && (summary.style.display = 'block');
            $.magnificPopup.open(magnificPopupConfig['auth']);
        }
    };

    $('.header-bottom-content-icons .icon-user, .open-login-popup').magnificPopup(magnificPopupConfig['auth']);

    // > POPUP REGISTRATION
    //=================================================================
    var regMsg, $regBlock, $regConditions, $regSubmitBtn;

    $regBlock = $('.user-register-block');
    $regConditions = $('#conditions');
    $regSubmitBtn = $('.btn-submit-form', $regBlock);

    $regConditions.change(function() {
        if (this.checked) {
            $regSubmitBtn.removeAttr('disabled');
        } else {
            $regSubmitBtn.attr('disabled', 'disabled');
        }
    });
    $regConditions.trigger('change');

    // reg link on complaints auth form and popup auth form
    $('#popup-reg-link, #register-popup-link').magnificPopup(magnificPopupConfig['reg']);

    var texts = window.bmrAuth.msgTexts;
    regMsg = {
        success: [
            '<div class="user-register-block success">',
            '<div class="background">',
            '<div class="user-register-header">',
            '<h3>' + texts.success.h3 + '</h3>',
            '</div>',
            '<div class="register-message-container">',
            '<span class="register-message">' + texts.success.registerMessage.replace('{uri}', bmrAuth.profileurl) + '</span>',
            '<span class="register-message-info">' + texts.success.registerMessageInfo + '</span>',
            '<span class="icon-with-lines"><span class="icon-checkmark"></span></span>',
            '<span class="register-message-thx">' + texts.success.registerMessageThx + '</span>',
            '</div>',
            '</div>',
            '</div>'
        ].join(' '),
        error: [
            '<div class="user-register-block error">',
            '<div class="background">',
            '<div class="user-register-header">',
            '<h3>' + texts.error.h3 + '</h3>',
            '</div>',
            '<div class="register-message-container">',
            '<span class="register-message">' + texts.error.registerMessage,
            '<span class="register-message-error">' + texts.error.registerMessageError.replace('{code}', '<i id="error-code"></i>') + '</span></span>',
            '<span class="register-message-info">',
            texts.error.registerMessageInfo + '<br>',
            '<a href="mailto:support@bookmakersrating.ru">support@bookmakersrating.ru</a>',
            '</span>',
            '<span class="icon-with-lines"><span class="icon-close"></span></span>',
            '<span class="register-message-thx">' + texts.error.registerMessageThx + '</span>',
            '</div>',
            '</div>',
            '</div>'
        ].join(' '),
        confirm: [
            '<div class="user-register-block confirm">',
            '<div class="background">',
            '<div class="user-register-header">',
            '<h3>' + texts.confirm.h3 + '</h3>',
            '</div>',
            '<div class="register-message-container">',
            '<span class="register-message">' + texts.confirm.registerMessage + '</span>',
            '<span class="register-message-info">' + texts.confirm.registerMessageInfo + '</span>',
            '<span class="icon-with-lines"><span class="icon-checkmark"></span></span>',
            '<span class="register-message-thx">' + texts.confirm.registerMessageThx + '</span>',
            '</div>',
            '</div>',
            '</div>'
        ].join(' '),
        passreset: [
            '<div class="user-login-block pass-sent-notify">',
            '<div class="background">',
            '<div class="user-login-header"><h3>' + texts.passreset.h3 + '</h3></div>',
            '<div class="register-message-container">',
            '<span class="register-message">' + texts.passreset.registerMessage + '</span>',
            '<span class="icon-with-lines"><span class="icon-checkmark"></span></span>',
            '<span class="register-message-thx">' + texts.passreset.registerMessageThx + '</span>',
            '</div>',
            '</div>',
            '</div>'
        ].join(' ')
    };

    $('#registerform').submit(function() {

        if (!$regConditions.is(':checked')) {
            return false;
        }
        var self = $(this);
        var spinner = $('.bmr-loading-spinner');

        var data = self.serializeArray();
        data = formatData(data, bmrAuth.action['register']);

        $.ajax({
            url: bmrAuth.ajaxurl,
            type: "post",
            dataType: 'json',
            data: data,
            beforeSend: function() {
                spinner.show();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                spinner.hide();
                $.magnificPopup.open({
                    items: {
                        src: regMsg['error']
                    }, closeMarkup: '<span class="mfp-close icon-close"></span>', fixedContentPos: true, callbacks: {
                        beforeOpen: function() {
                            this.st.mainClass = 'mfp-zoom-in';
                        }
                    }
                });

                var $errorCode = $('#error-code');
                if ($errorCode.length > 0) {
                    $errorCode.text(jqXHR.status);
                }
            },
            success: function(response) {
                spinner.hide();

                if (response !== 0 && typeof response != 'undefined') {
                    if (response.success) {
                        $.magnificPopup.open({
                            items: {
                                src: regMsg['success']
                            },
                            closeMarkup: '<span class="mfp-close icon-close"></span>',
                            fixedContentPos: true,
                            callbacks: {
                                beforeOpen: function() {
                                    this.st.mainClass = 'mfp-zoom-in';
                                }
                            }
                        });
                        setTimeout(function() {
                            $.magnificPopup.close();
                            if (typeof bmrAuth.authSuccess === 'function') {
                                bmrAuth.authSuccess() && location.assign(response.data.redirect_to);
                            } else {
                                location.assign(response.data.redirect_to);
                            }
                        }, 1500);
                    } else {
                        spinner.hide();
                        displayErrors(response, self);
                    }
                }
            }
        });
        return false;
    });

    // > POPUP AUTH
    //=================================================================
    var $authForm;
    $authForm = $('#loginform');

    $authForm.submit(function() {
        var self = $(this);
        var spinner = $('.bmr-loading-spinner', self.parent());

        var data = self.serializeArray();
        data = formatData(data, bmrAuth.action['auth']);

        $.ajax({
            url: bmrAuth.ajaxurl,
            type: "post",
            dataType: 'json',
            data: data,
            beforeSend: function() {
                spinner.show();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                spinner.hide();
                $.magnificPopup.open({
                    items: {
                        src: regMsg['error']
                    }, closeMarkup: '<span class="mfp-close icon-close"></span>', fixedContentPos: true, callbacks: {
                        beforeOpen: function() {
                            this.st.mainClass = 'mfp-zoom-in';
                        }
                    }
                });
                $('.user-register-block.error h3').text('Вход на сайт');

                var $errorCode = $('#error-code');
                if ($errorCode.length > 0) {
                    $errorCode.text(jqXHR.status);
                }
            },
            success: function(response) {
                spinner.hide();

                if (response !== 0 && typeof response != 'undefined') {
                    if (response.success) {
                        $.magnificPopup.close();
                        if (typeof bmrAuth.authSuccess === 'function') {
                            bmrAuth.authSuccess() && location.assign(response.redirect_to);
                        } else {
                            location.assign(response.redirect_to);
                        }
                    } else {
                        spinner.hide();
                        displayErrors(response, self);
                    }
                }
            }
        });
        return false;
    });

    // > POPUP RECOVER PASSWORD
    //=================================================================
    if ($recoverPassFormContainer.length > 0) {
        var $recoverForm = $('form', $recoverPassFormContainer);

        $recoverForm.submit(function() {
            var self = $(this);
            var spinner = $('.bmr-loading-spinner', $recoverPassFormContainer);

            var data = self.serializeArray();
            data = formatData(data, bmrAuth.action['recover']);

            $.ajax({
                url: bmrAuth.ajaxurl,
                type: "post",
                dataType: 'json',
                data: data,

                beforeSend: function() {
                    spinner.show();
                },
                success: function(response) {
                    if (response !== 0 && typeof response != 'undefined') {
                        if (response.success) {
                            $.magnificPopup.open({
                                items: {
                                    src: regMsg['passreset']
                                }, closeMarkup: '<span class="mfp-close icon-close"></span>', fixedContentPos: true, callbacks: {
                                    beforeOpen: function() {
                                        this.st.mainClass = 'mfp-zoom-in';
                                    }
                                }
                            });
                        } else {
                            spinner.hide();
                            displayErrors(response, self);
                        }
                    }
                },
                complete: function() {
                    spinner.hide();
                }
            });
            return false;
        });
    }

    // > POPUP RESET PASSWORD
    //=================================================================
    if ($resetPassFormContainer.length > 0) {
        var $rpForm = $('form', $resetPassFormContainer);

        $rpForm.submit(function() {
            var self = $(this);
            var spinner = $('.bmr-loading-spinner', $resetPassFormContainer);

            var data = self.serializeArray();
            data = formatData(data, bmrAuth.action['reset']);

            $.ajax({
                url: bmrAuth.ajaxurl,
                type: "post",
                dataType: 'json',
                data: data,

                beforeSend: function() {
                    spinner.show();
                },
                success: function(response) {
                    if (response !== 0 && typeof response != 'undefined') {
                        if (response.success) {
                            //profileBlockUpdate(response.data);
                            //$.magnificPopup.close();
                            location.assign('/');
                            $.magnificPopup.close();
                        } else {
                            spinner.hide();
                            displayErrors(response, self);
                        }
                    }
                },
                complete: function() {
                    spinner.hide();
                }
            });
            return false;
        });
    }


    // > HELPER FUNCTIONS
    //=================================================================
    var profileBlockUpdate = function(data)
    {
        // Show registered user menu
        var $usrImgBlock = $('.logout-block .user-image').html('');
        // Remove show auth icon
        $('.icon-user').remove();
        // Display avatar or initials
        if (typeof data['display_name'] !== 'undefined') {
            $usrImgBlock.append('<span class="logout-text">' + data['display_name'] + '</span>');
        } else if (typeof data['ava'] !== 'undefined') {
            $usrImgBlock.append('<img class="avatar" src="' + data['ava'] + '" />');
        }
        // Add logout link
        $('.login-user-menu').append(
            '<li><a href="' + data["logout_link"] + '">Выйти</a></li>'
        );
        // Add logged-in class
        $('.logout-block').addClass('logged-in');
    };

    var formatData = function(data, value)
    {
        data.push({
            name: 'action',
            value: value.action
        });
        data.push({
            name: '_auth_nonce',
            value: value.nonce
        });
        return $.param(data);
    };

    function displayErrors(response, self)
    {
        var $summary = $('.summary');
        //clean errors
        $('.has-error').removeClass('has-error');
        $('.has-popover', self).removeClass('has-popover').popover('destroy');
        $summary.html('').hide();

        var elem = {};
        $.each(response.errors, function(i, v) {
            if (i != 'summary') {
                elem = $('#' + i);
                elem.attr('data-content', v);
                if (elem.length != 0) {
                    elem.addClass('has-popover');
                    elem.prev().addClass('has-error');
                }
            } else if (i == 'summary') {
                $summary.html(v).show();
            }
        });
        $('.has-popover', self).popover({
            title: 'Ошибка!',
            html: true,
            trigger: 'hover',
            placement: 'top'
        });
    }

});