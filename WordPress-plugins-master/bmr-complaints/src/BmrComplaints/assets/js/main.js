jQuery(document).ready(function ( $ ) {
    "use strict";
    console.log(document.querySelector('.complaint-form-wrapper'));
    if( document.querySelector('.complaint-form-wrapper')) {
        if (!document.body.classList.contains('logged-in')) {
            bmrAuth.show('auth', translates.complaintAuthNotice);
            var els = [].slice.call(document.getElementById('mr-complaint-form').querySelectorAll('input[type=text], textarea'));
            els.map(function(el){
                el.setAttribute('readonly', '');
                addEvent(el, 'click', function(){
                    bmrAuth.show('auth', translates.complaintAuthNotice);
                });
            });
            return;
        }
    }

    $(function () {

        /**
         * BOOKMAKERS LIST AUTOCOMPLETE ON COMPLAINTS FORM
         */
        var $bookmakerInput = $('#bmr_bookmaker');

        if ($bookmakerInput.length > 0 && typeof $.fn.typeahead !== 'undefined') {
            var bmrBh = new Bloodhound({
                datumTokenizer: function (datum) {
                    return Bloodhound.tokenizers.whitespace(datum.name);
                },
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                remote: ajaxurl + '?action=get_bookmakers&query=%QUERY'
            });

            bmrBh.initialize();

            $bookmakerInput.typeahead({
                minLength: 2,
                highlight: true
            }, {
                name: 'bookmakers',
                displayKey: 'name',
                source: bmrBh.ttAdapter()
            });
        }
        // AUTOCOMPLETE END


        var currViewportWidth = $(window).width();

        var mobileOS = navigator.userAgent.match(/Mobile|iP(hone|od|ad)|Android|BlackBerry|IEMobile|Kindle|NetFront|Silk-Accelerated|(hpw|web)OS|Fennec|Minimo|Opera M(obi|ini)|Blazer|Dolfin|Dolphin|Skyfire|Zune/g) ? true : false;

        if (mobileOS) {
            $('select option[value=""]').remove();

            $('.filter-dropdown').each(function() {
                var width = $(this).width();
                var txtWidth = getTextWidth($(this).find('option:selected'));
                var indent = (width - txtWidth) / 2  - 5;
                $(this).css('text-indent', indent+'px');
            });
        }

        function getTextWidth(selector) {
            if ($(selector).length < 1) {
                return 0;
            }
            var font = $(selector).css('font');
            var text = $(selector).text();
            var html = '<span id="to-remove">' + text + '</span>';
            $('body').append(html);
            var $toRemove= $('#to-remove');
            $toRemove.css('font', font);
            var width = $toRemove.width();
            $toRemove.remove();
            return width;
        }

        /**
         * TOGGLE ARCHIVE DESCRIPTION
         */
        var $expandDescriptionBtn = $('.archive-read-more-btn');

        if ($expandDescriptionBtn.length > 0) {
            var archiveDescStates = {
                1 : 'Развернуть описание',
                0 : 'Свернуть описание'
            };

            $expandDescriptionBtn.click(function() {
                var $text = $(this).parent().prev('.archive-description-content');
                var aState =  $text.is(':visible') ? 1 : 0;

                if (aState === 0) {
                    $text.velocity('slideDown', 400);
                } else {
                    $text.velocity('slideUp', 400);
                }
                $(this).text(archiveDescStates[aState]);
                return false;
            });
        }

        var $dropdownFilter = $('.filter-dropdown');
        $dropdownFilter.fancySelect({
            forceiOS: false
        });

        var commentHash = window.location.hash;
        if (!empty(commentHash)) {
            $(commentHash, '.complaints-discussion').addClass('current-comment');
        }

        /**
         * COMPLAINT FORM
         */
        var $form, $summary, errorClass, successClass, inProgress, $pageTitle, $pageContent, $conditions, $complaintSubmitBtn;

        $pageTitle = $('.container-address-header h3');
        $pageContent = $('.container-address-info .content');

        inProgress = false;
        errorClass = 'bs-callout bs-callout-danger';
        $form = $('#bmr-complaint-form');
        $summary = $('#summary');
        $conditions = $('#complaint-conditions');
        $complaintSubmitBtn = $('#complaint-submit-btn');

        $conditions.change(function () {
            if (this.checked) {
                $complaintSubmitBtn.removeAttr('disabled');
            } else {
                $complaintSubmitBtn.attr('disabled', 'disabled');
            }
        });
        $conditions.trigger('change');

        // Disable form submit on enter
        $('input', $form).on("keyup keypress", function (e) {
            var code = e.keyCode || e.which;
            if (code == 13) {
                e.preventDefault();
                return false;
            }
        });

        $('.has-spinner').click(function() {
            $(this).addClass('processing');
        });

        $form.submit(function () {
            if (!$conditions.is(':checked')) {
                return false;
            }

            /*$.ajax({
             url: ajaxurl,
             dataType: 'json',
             data: {
             action: bmr.action,
             security: bmr.security
             },
             beforeSend: function() {
             if (inProgress) {
             return false;
             }
             $summary.removeAttr('class').addClass('hidden');
             $form.find(':submit').html("<i class='icon-send-plane'></i>Отправляется...<i class='icon-send-plane'></i>");
             inProgress = true;
             },
             error: function (jqXHR, textStatus, errorThrown) {
             var msg = 'Возникла проблема.';

             switch (jqXHR.status) {
             case 400:
             msg = 'Сервер не смог обработать ваш запрос.';
             break;
             case 401:
             msg = 'Несанкционированный доступ.';
             break;
             case 403:
             msg = 'Сервер недоступен.';
             break;
             case 404:
             msg = 'Не удалось связаться с сервером.';
             break;
             case 500:
             msg = 'На стороне сервера произошла ошибка.';
             break;
             case 503:
             msg = 'Сервер недоступен.';
             break;
             default:
             var error = typeof errorThrown === 'undefined' ? '' : ' [' + errorThrown + ']';
             msg += error;
             break;
             }
             $summary.removeAttr('class').addClass(errorClass);
             $summary.find('h4').text('Ошибка ' + jqXHR.status);
             $summary.find('p').text(msg);
             },
             success: successCallback,

             complete: function() {
             afterRecieved();
             }
             });*/

            $(this).ajaxSubmit({
                dataType: 'json',
                data: {
                    action: bmr.action,
                    security: bmr.security
                },
                beforeSubmit: function () {
                    if (inProgress) {
                        return false;
                    }
                    $summary.removeAttr('class').addClass('hidden');
                    $form.find(':submit').html("<i class='icon-send-plane'></i>Отправляется...<i class='icon-send-plane'></i>");
                    inProgress = true;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    var msg = 'Возникла проблема.';

                    switch (jqXHR.status) {
                        case 400:
                            msg = 'Сервер не смог обработать ваш запрос.';
                            break;
                        case 401:
                            msg = 'Несанкционированный доступ.';
                            break;
                        case 403:
                            msg = 'Сервер недоступен.';
                            break;
                        case 404:
                            msg = 'Не удалось связаться с сервером.';
                            break;
                        case 500:
                            msg = 'На стороне сервера произошла ошибка.';
                            break;
                        case 503:
                            msg = 'Сервер недоступен.';
                            break;
                        default:
                            var error = typeof errorThrown === 'undefined' ? '' : ' [' + errorThrown + ']';
                            msg += error;
                            break;
                    }
                    $summary.removeAttr('class').addClass(errorClass);
                    $summary.find('h4').text('Ошибка ' + jqXHR.status);
                    $summary.find('p').text(msg);
                },

                success: successCallback,

                complete: function() {
                    afterRecieved();
                }

            });
            return false;
        });

        function successCallback(response) {
            if (!empty(response)) {
                if (response.success === true) {
                    $form.remove();
                    $('.headInfoForm,.saveEars').remove();
                    $('.container-address-header-menu').remove();
                    $('#comments').remove();
                    $pageTitle.text('Жалоба успешно отправлена!');

                    var content = '';
                    if (empty(bmr.form_success_txt)) {
                        content = [
                            'Мы рассмотрим Вашу жалобу в ближайшее время, и сделаем все, что бы решить Вашу проблему.<br>',
                            'Мы свяжемся с Вами по электронной почте.<br><br>',
                            '<span class=grey">Если у вас остались какие либо вопросы, пишите нам сюда: </span>',
                            '<a href="mailto:info@bookmakersrating.ru">info@bookmakersrating.ru</a><br>'
                        ].join('');
                    } else {
                        content = bmr.form_success_txt;
                    }

                    $pageContent.html(content);
                    $pageContent.css({
                        color: '#131313',
                        'border-bottom' : '10px solid #f1f2f2',
                        'margin-bottom' : '0',
                        'padding' : '15px'
                    });

                    if (!empty(response.data)) {
                        content = '';
                        var relatedPost = '';
                        $.each(response.data, function (index, object) {
                            relatedPost = [
                                '<div class="single-complaint ' + object.status["slug"] + ' inner-content-with-bg">',
                                '<span class="image-category">' + object.status["name"] + '</span>',
                                '<h2><a href="' + object.link + '">' + object.title + '</a></h2>',
                                '<div class="date">',
                                '<span class="icon-calendar4 mainsite-inline"></span>',
                                '<span class="mainsite-italic mainsite-inline"> ' + object.date + '</span>',
                                '</div>',
                                '<div class="complaint-content">',
                                '<p>' + object.content + '</p>',
                                '</div>',
                                '<div class="category-block">',
                                '<a href="' + object.type_link + '"><span class="category">' + object.type['name'] + '</span><span></span></a>',
                                '</div>',
                                '</div>'
                            ].join('');
                            content += relatedPost;
                        });
                        $('#related-complaints').append(content).show();
                    }
                    var $emptyP = $('.complaint-form-wrapper').next('p:contains("")');
                    if ($emptyP.length > 0) {
                        $emptyP.remove();
                    }
                    $pageContent.show();
                    scrollTo('.container-address-header', 1);
                    return;

                } else {
                    $summary.removeAttr('class').addClass(errorClass);
                    if (!empty(response.errors)) {
                        $summary.find('h4').text('Ошибка проверки введенных данных');
                        var errors = '';
                        $form.find('.form-row').removeClass('has-error');
                        $.each(response.errors, function (index, elem) {
                            errors += '- ' + elem + '<br>';
                            $form.find('#' + index).parents('.form-row').addClass('has-error');
                        });
                        $summary.find('p').html(errors);
                    } else {
                        $summary.find('h4').text('Ошибка');
                        $summary.find('p').text(response.msg);
                    }
                }
            } else {
                $summary.removeAttr('class').addClass(errorClass);
                $summary.find('h4').text('Ошибка');
                $summary.find('p').text('Возникла неизвестная проблема.');
            }
        }

        function afterRecieved() {
            scrollTo('#summary', 250);
            $form.find(':submit').html("<i class='icon-send-plane'></i>Отправить<i class='icon-send-plane'></i>");
            inProgress = false;
        }

        //========================================================
        // Dropdown menu item click
        //========================================================
        var $cplTypeContainer = $('.complaint-type-dropdown');
        var $cplBookmakerContainer = $('.complaint-bookmaker-dropdown');

        $dropdownFilter.on('change.fs change', function() {
            var filters = {};
            var type, bookmaker;

            var search = window.location.search;

            if (!empty(search)) {
                type = search.match(/(?:complaint_type=)(.+)(?:&)?/i);
                if (type != null) {
                    type = type[1];
                }
                bookmaker = search.match(/(?:complaint_bookmaker=)(.+)(?:&)?/i);
                if (bookmaker != null) {
                    bookmaker = bookmaker[1];
                }
            }

            var typeSelected = $cplTypeContainer.find('select').val();
            if (type != typeSelected) {
                if (typeSelected == '*') {
                    type = '';
                } else {
                    type = typeSelected;
                }
            }

            var bookmakerSelected = $cplBookmakerContainer.find('select').val();
            if (bookmaker != bookmakerSelected) {
                if (bookmakerSelected == '*') {
                    bookmaker = '';
                } else {
                    bookmaker = bookmakerSelected;
                }
            }

            if (!empty(type)) {
                filters.complaint_type = type;
            }
            if (!empty(bookmaker)) {
                filters.complaint_bookmaker = bookmaker;
            }

            var params = encodeQueryData(filters);

            var assign_path = '/' + bmr.complaint_post_type_slug + '/';
            !empty(params) && (assign_path += '?' + params);
            window.location.assign(assign_path);
        });

        //========================================================
        // Grid view style
        //========================================================
        /**********************************************
         * Control posts block view style
         **********************************************/
            /*

        var $mainBlock = $('.changeable-view');
        var $changeViewBtn = $('.change-view-btn');
        var isList = $mainBlock.hasClass('view-list');

        if ($mainBlock.length > 0) {

            var viewStyle = $.cookie('complaints-view-style');
            if (typeof viewStyle !== 'undefined') {
                $changeViewBtn.removeClass('active');
                $('.change-view-btn[data-view-class="' + viewStyle + '"]').addClass('active');
                $mainBlock.removeClass('view-grid view-list').addClass(viewStyle);
                isList = $mainBlock.hasClass('view-list');
            }

            $changeViewBtn.click(function () {
                $changeViewBtn.removeClass('active');
                $(this).addClass('active');
                $mainBlock.removeClass('view-grid view-list').addClass($(this).data('viewClass'));
                isList = $mainBlock.hasClass('view-list');

                $.cookie('complaints-view-style', $(this).data('viewClass'));
                /* TRUNCATE HEADINGS */
        /*
                applyHeadingsTruncate(isList);
            });

            var applyHeadingsTruncate = function(isList) {
                setTimeout(function() {
                    if (currViewportWidth <= 950) {
                        truncateText('.has-image .post-data .post-heading h2', 3);
                        truncateText('.no-image .post-thumb .post-heading h2', 5);
                    } else {
                        if (isList) {
                            truncateText('.has-image .post-data .post-heading h2', 2);
                            truncateText('.no-image .post-thumb .post-heading h2', 6);
                        } else {
                            truncateText('.has-image .post-data .post-heading h2', 3);
                            truncateText('.no-image .post-thumb .post-heading h2', 8);
                        }
                    }
                }, 1);
            };

            if (currViewportWidth <= 950) {
                isList = true;
                $changeViewBtn.removeClass('active');
                $('.list-btn').addClass('active');
                $mainBlock.removeClass('view-grid').addClass('view-list');
            }
            /* TRUNCATE HEADINGS *//*
            applyHeadingsTruncate(isList);

            $(window).on('resize', function () {
                currViewportWidth = $(window).width();

                if (currViewportWidth <= 950) {
                    $changeViewBtn.removeClass('active');
                    $('.list-btn').addClass('active');
                    $mainBlock.removeClass('view-grid').addClass('view-list');
                }
                isList = $mainBlock.hasClass('view-list');
                applyHeadingsTruncate(isList);
            });

        }
        */
        //========================================================
        // Load file
        //========================================================

        var $fileList, $attachmentIds, $fileForm, $fileErrorContainer, $fileErrorSpan;

        $fileList = $('.fileArchive');
        $attachmentIds = $('#bmr_attachments');
        $fileForm = $("#bmr-complaint-form");

        $fileErrorContainer = $('.file-error');
        $fileErrorSpan = $('.file-error-msg', $fileErrorContainer);

        $('.progressBar').css('width', '0%');
        var jqXHR = $('#bmr_file_2, #bmr_file').fileupload({
            url: ajaxurl,
            formData: {action: bmr.file_action},
            dataType: 'json',
            done: function (e, data) {
                $('.progressBar', $fileForm).css('width', '100%');
                var response = data.response();
                response = response.result;
                $('.cancelFile').addClass('hidden');

                if (response.success) {
                    var file = response.file;
                    $('.fileArchive').removeClass('hidden');

                    var filePreviewHtml = [
                        '<div class="file-preview">',
                        '<img src="' + file.url_thumb + '" />',
                        '<span class="file-title">' + file.name + '</span>',
                        '<span class="file-size"> (' + file.size + ')</span>',
                        '<div data-id="' + file.id + '" class="delFile"><span class="file-action-txt">Удалить</span></div>',
                        '</div>'
                    ].join('');

                    $fileList.append(filePreviewHtml);
                    var ids = $attachmentIds.val();
                    if (ids == '') {
                        $attachmentIds.val(file.id);
                    } else {
                        ids = ids + ',' + file.id;
                        $attachmentIds.val(ids);
                    }
                } else {
                    if ($fileErrorContainer.length > 0) {
                        $fileErrorContainer.show();
                        $fileErrorSpan.html(response.error);
                    }
                }
                $('.fileLoadedPARENT', $fileForm).addClass('hidden');
            },
            progressall: function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                $('.progressBar').css(
                    'width',
                    progress + '%'
                );
            },
            complete: function () {
                $('.progressBar',$fileForm).css('width', '0');
            },
            start: function () {
                if ($fileErrorContainer.length > 0) {
                    $fileErrorContainer.hide();
                    $fileErrorSpan.html('');
                }
                $('.progressBar', $fileForm).css('width', '10%');
            },
            change: function (e, data) {
                $('.fileLoaded', $fileForm).show().html(data.files[0].name + ' (' + humanFileSize(data.files[0].size) + ')');
                $('.cancelFile', $fileForm).removeClass('hidden');
                $('.fileLoadedPARENT', $fileForm).removeClass('hidden');
                fileClicked = false;
            }
        });

        var fileClicked = false;

        $('.bmr-file-container').click(function() {
            if (!fileClicked) {
                $fileForm = $(this).parents('form');
                $attachmentIds = $fileForm.find('#bmr_attachments');
                $fileList = $fileForm.find('.fileArchive');
                $fileErrorContainer = $('.file-error', $fileForm);
                $fileErrorSpan = $('.file-error-msg', $fileErrorContainer);

                fileClicked = true;
            }
        });

        $('.cancelFile').click(function () {
            jqXHR.abort();
            $(this).addClass('hidden');
        });

        $(document).on('click', '.delFile', function () {
            var self, $fileparent, id, comment_id;

            comment_id = -1;
            var $comParent = $(this).parents('.comment-body');
            if ($comParent.length > 0) {
                comment_id = $comParent.attr('id');
                comment_id = comment_id.match(/\d+/)[0];
            }

            self = $(this);
            $fileparent = $(this).parents('.file-preview');
            id = $(this).data('id');
            self.hide();

            $.ajax({
                url: ajaxurl,
                type: 'GET',
                data: {
                    action: bmr.file_delete_action,
                    id: id,
                    comment_id: comment_id
                },
                success: function (response) {
                    if (response.success) {
                        $fileparent.fadeOut(function () {
                            $(this).remove();
                        });
                        var ids = $attachmentIds.val();
                        ids = ids.replace(new RegExp(',?' + id, 'g'), "");
                        $attachmentIds.val(ids);
                    } else {
                        self.show();
                    }
                }
            });
        });

        //========================================================
        // Comment reply to
        //========================================================
        var $replyForm, $comParent, $user, userId, userIdOriginal, $replyTo, parentComId;
        $replyForm = $('#bmr-comment-reply');

        $('.comment-reply-link').click(function (e) {
            resetReplyForm();

            toggleCommentData('show');

            $comParent = $(this).parents('.comment-body');

            parentComId = $comParent.attr('id');
            parentComId = parentComId.match(/\d+/)[0];
            $replyForm.find('#comment_parent').val(parentComId);

            $replyForm.insertAfter($comParent).show();

            $user = $comParent.find('[id^=user]');
            userId = $user.data('replyTo');
            userIdOriginal = $user.data('replyToOriginal');

            $replyTo = $('#bmr-reply-to');
            $replyTo.val(userId);
            $('#bmr-reply-to-original').val(userIdOriginal);
            e.preventDefault();
        });

        //========================================================
        // Comment edit
        //========================================================
        var
            $editBtn                // edit btn
            ,$commentText           // comment text
            ,$commentActions        // comment action buttons
            ,$commentBody           // comment body
            ,$commentFiles          // attached comment files
            ,$commentFileArchive    // comment file archive
            ,$replyBtn
            ,$replyAction
            ,oldReplyBtnTxt
            ,oldActionType
            ;

        $editBtn = $('.comment-edit');
        $replyBtn = $('#reply-submit');
        $replyAction = $('[name="action_type"]', $replyForm);
        oldReplyBtnTxt = $replyBtn.html();
        oldActionType = $replyAction.val();

        $editBtn.click(function() {
            toggleCommentData('show');

            $commentBody = $(this).parents('.comment-body');
            $commentText = $('.comment-text', $commentBody);
            $commentActions = $('.complaint-comment-actions', $commentBody);
            $commentFiles = $('.comment-file', $commentBody);
            $commentFileArchive = $('.fileArchive', $commentBody);

            var content = $commentText.text();

            toggleCommentData('hide');

            $commentBody.append($replyForm);
            $replyForm.find('textarea').text(content);
            $replyForm.show();

            if (!empty($commentFiles)) {
                var file, filesContent = '';
                $commentFiles.each(function(v){
                    file = [
                        '<div class="file-preview">',
                        '<img src="'+$(this).data('imgUrl')+'" alt="'+ $(this).attr('title') +'">',
                        '<span class="file-title">'+ $(this).attr('title') +'</span>',
                        '<span class="file-size"> ('+$(this).data('imgSize')+')</span>',
                        '<div data-id="'+$(this).data('imgId')+'" class="delFile"><span class="file-action-txt">Удалить</span></div>',
                        '</div>'
                    ].join(' ');
                    filesContent += file;
                });
                $('.fileArchive', $replyForm).append(filesContent).removeClass('hidden');
            }
            $replyAction.val('edit');
            $replyBtn.html('<span class="btn-spinner"><i class="fa fa-refresh fa-spin"></i></span>Сохранить');

            return false;
        });

        var isSaving = false, isCommentDataVisible = true;

        $replyBtn.click(function() {
            if ($replyAction.val() == 'edit') {
                $comParent = $(this).parents('.comment-body');
                parentComId = $comParent.attr('id');
                parentComId = parentComId.match(/\d+/)[0];
                var params = $replyForm.find('form').serializeArray();

                var commentId = {
                    name: 'comment_id',
                    value: parentComId
                };
                params.push(commentId);
                var formData = $.param(params);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    beforeSend: function () {
                        if (isSaving) {
                            return false;
                        }
                        $replyBtn.addClass('processing');
                        isSaving = true;

                    },
                    error: function () {
                        resetReplyForm();
                        toggleCommentData('show');
                        isSaving = false;
                        $replyBtn.removeClass('processing');
                    },
                    success: function (response) {
                        if (response.success) {
                            resetReplyForm();
                            $('.fileArchive', $comParent).html('').removeClass('hidden');
                            if (!empty(response.data.attachments)) {
                                var attachments = '';
                                $.each(response.data.attachments, function(i,v) {
                                    attachments += v;
                                });
                                $('.fileArchive', $comParent).append(attachments);
                            }
                            $('.comment-text', $comParent).html('<p>'+response.data['comment_content']+'</p>');
                        }
                        isSaving = false;
                        $replyBtn.removeClass('processing');
                        toggleCommentData('show');
                    }
                });
                return false;
            }
        });

        function toggleCommentData(action) {
            if (typeof $commentText == 'undefined') {
                return;
            }
            if (action == 'hide') {
                $commentText.hide();
                $commentActions.hide();
                $commentFileArchive.hide();
            } else if (action == 'show') {
                $commentText.show();
                $commentActions.show();
                $commentFileArchive.show();
            }
        }

        function resetReplyForm()
        {
            $replyAction.val(oldActionType);
            $replyBtn.html(oldReplyBtnTxt);
            $replyForm.hide();
            $('.comment-txtarea', $replyForm).text('');
            $replyForm.insertAfter('#respond');
            $('.fileArchive', $replyForm).html('').addClass('hidden');
        }

        /**
         * Truncates multiline text (using truncate.js plugin)
         * @param selector jquery object or selector
         * @param lines number of lines
         */
        function truncateText(selector, lines) {
            var $selector = $(selector);
            if (typeof $selector === 'undefined' || $selector.length === 0 || typeof $.fn.truncate === 'undefined') {
                return false;
            }

            var truncateData;
            $selector.each(function() {
                truncateData = $(this).data('jqueryTruncate');

                if (typeof truncateData !== 'undefined') {
                    if (truncateData.isCollapsed) {
                        $(this).truncate('expand');
                    }
                    $(this).removeData('jqueryTruncate');
                }
            });

            var lineHeight =  parseInt($selector.css('line-height'),10);
            if (lineHeight) {
                $selector.truncate({
                    lines: lines,
                    lineHeight: lineHeight
                });
            }
        }

    });
    addEvent('textarea', 'keyup', function(){
        this.style.height = '5px';
        this.style.height = (this.scrollHeight + 10)+"px";
    });

});



function empty(mixed_var) {
    var undef, key, i, len;
    var emptyValues = [undef, null, false, 0, '', '0'];

    for (i = 0, len = emptyValues.length; i < len; i++) {
        if (mixed_var === emptyValues[i]) {
            return true;
        }
    }
    if (typeof mixed_var === 'object') {
        for (key in mixed_var) {
            return false;
        }
        return true;
    }

    return false;
}

function humanFileSize(bytes, si) {
    var thresh = si ? 1000 : 1024;
    if(bytes < thresh) return bytes + ' B';
    var units = si ? ['kB','MB','GB','TB','PB','EB','ZB','YB'] : ['KB','MB','GB','TB','PB','EB','ZB','YB'];
    var u = -1;
    do {
        bytes /= thresh;
        ++u;
    } while(bytes >= thresh);
    return bytes.toFixed(1)+' '+units[u];
};

// Usage:
//   var data = { 'first name': 'George', 'last name': 'Jetson', 'age': 110 };
//   var querystring = EncodeQueryData(data);
//
function encodeQueryData(data)
{
    var ret = [];
    for (var d in data)
        ret.push(encodeURIComponent(d) + "=" + encodeURIComponent(data[d]));
    return ret.join("&");
}

// kapper complaint single menu fix
+function(){
    var kapper_content_inner = document.body.querySelector('.content.inner.kapper-complaint');

    if (kapper_content_inner) {
        var menu_all = document.body.querySelector(".sidebar-left-menu a[href$='?kapper_complaints']");
        if (menu_all) {
            menu_all.parents('ul') && (menu_all.parents('ul').style.display = 'block');
            menu_all.parents('ul') && menu_all.parents('ul').parents('ul') && (menu_all.parents('ul').parents('ul').style.display = 'block');
        }

        var status = kapper_content_inner.dataset.status;
        if (status) {
            var menu_item = document.body.querySelector(".sidebar-left-menu a[href*='complaint_type=" + status + "']");
            menu_item && menu_item.classList.add('active');
        } else {
            menu_all && menu_all.classList.add('active');
        }
    }
}();


// complaint form fix
+function(){
    if (
        document.querySelector('.complaint-form-wrapper') ||
        document.querySelector('.bmr-auth-form-container')
    ) {
        document.querySelector('.complaint-form-wrapper') && document.body.classList.add('body-complaint-form-wrapper');
    }
}();
