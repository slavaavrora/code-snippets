addEvent(window, 'DOMContentLoaded', function()
{
    "use strict";

    addEvent('.caps-input', 'change', function()
    {
        var userId = this.parents('.user-info').dataset['id'],
            action = (this.checked ? 'add' : 'remove') + '_' + this.name,
            $capsRow = this.parents('.row-caps');

        ajax(location.href, {
            method: 'post',
            data: {
                user_id: userId,
                single_action: action
            },
            beforeSend: function() {
                $capsRow.classList.add('is-disabled');
            },
            complete: function() {
                $capsRow.classList.remove('is-disabled');

                if (!$capsRow.querySelectorAll(':checked').length) {
                    var parent = $capsRow.parents('tr');
                    parent.parentNode.removeChild(parent);
                }
            }
        })
    });

    function modifyQueryParams(params, uri)
    {
        var regex;
        uri = uri || location.href;
        for (var key in params) {
            if (!params.hasOwnProperty(key)) {
                continue;
            }

            regex = new RegExp('&' + key + '=[^&]+', 'g');

            if (!params[key].length) {
                uri = uri.replace(regex, '');
            } else if (uri.match(regex)) {
                uri = uri.replace(regex, '&' + key + '=' + params[key]);
            } else {
                uri += '&' + key + '=' + params[key];
            }
        }
        return uri;
    }

    function updateCommentVarsRefs()
    {
        $commentListContainer = document.getElementById('comments-list');
        $loadMoreButton = document.getElementById('comments-load-more');
        currentPage = 1;
        totalPages = $loadMoreButton ? $loadMoreButton.dataset['totalPages'] : 1;
    }

    function loadComments(uri, done)
    {
        if (!uri || $commentListContainer.classList.contains('has-spinner')) {
            return;
        }

        ajax(uri, {
            beforeSend: function() {
                $commentListContainer.classList.add('has-spinner');

                if ($commentsContainer.getBoundingClientRect().top < 0) {
                    window.bmr.scroll(0, 400);
                }
            },
            success: function(response) {
                var comments = document.createElement('div');
                comments.innerHTML = response;
                comments = comments.querySelector('#comments-container');
                comments && ($commentsContainer.innerHTML = comments.innerHTML);
                updateCommentVarsRefs();
                window.history && history.pushState({}, '', uri);

                typeof done === 'function' && done();
            },
            complete: function() {
                $commentListContainer.classList.remove('has-spinner');
            }
        })
    }

    var $quickActions = document.querySelector('.quick-action'), scrollY = 0;

    addEvent(window, 'scroll', function() {
        var top = $quickActions.getBoundingClientRect().top;

        if (top < 0) {
            $quickActions.classList.add('is-fixed');
            scrollY = window.scrollY;
        } else if (window.scrollY < scrollY) {
            $quickActions.classList.remove('is-fixed');
            scrollY = 0;
        }
    });

    var $commentsContainer    = document.getElementById('comments-container'),
        $commentListContainer = document.getElementById('comments-list'),
        $loadMoreButton       = document.getElementById('comments-load-more'),
        $commentsSort         = document.getElementById('comments-sort'),
        $editForm             = document.getElementById('edit-form'),
        $replyForm            = document.getElementById('reply-form'),
        $editEditor           = document.getElementById('content'),
        $replyEditor          = document.getElementById('comment'),
        commentClass          = '.comment',
        currentPage           = 1,
        totalPages;

    if (!$commentsContainer) {
        return;
    }

    totalPages = $loadMoreButton ? $loadMoreButton.dataset['totalPages'] : 1;

    addEvent('#search', 'keypress', function(e)
    {
        var query = this.value;
        if (e.keyCode == 13) { // ENTER
            var uri = modifyQueryParams({s: query, paged: ''});
            loadComments(uri);
        }
    });

    $commentsContainer.addListener('click', '.comment-ip', function(e)
    {
        e.preventDefault();
        var uri = modifyQueryParams({paged: ''}, this.href);
        loadComments(uri);
    });

    /**
     * CHECK/UNCHECK ALL CHECKBOXES
     */
    addEvent('#cb-select-all', 'click', function() {
        var checkboxes = $commentListContainer.querySelectorAll('.comment-id input');
        for (var i = checkboxes.length; i--;) {
            checkboxes[i].checked = this.checked;
        }
    });

    /**
     * TAB AJAX NAVIGATION
     */
    addEvent('.comments-wrap .nav-tab', 'click', function(e)
    {
        e.preventDefault();
        var tab = this,
            uri = modifyQueryParams({s: '', order: $commentsSort.value}, this.href);

        if (this.classList.contains('nav-tab-active')) {
            return;
        }

        loadComments(uri, function() {
            setActiveTab(tab);
        });
    });

    function setActiveTab($tab)
    {
        var tabs = $tab.parentNode.querySelectorAll('.nav-tab');
        for (var i = tabs.length; i--; tabs[i].classList.remove('nav-tab-active'));
        $tab.classList.add('nav-tab-active');
    }

    /**
     * LOAD MORE
     */
    var isLoadingMore = false;

    $commentsContainer.addListener('click', '#comments-load-more', function()
    {
        if (this.classList.contains('is-hidden') || isLoadingMore) {
            return;
        }

        var self = this;

        ajax(location.href + '&paged=' + (currentPage + 1), {
            beforeSend: function() {
                self.classList.add('infinite', 'animated', 'flash');
                isLoadingMore = true;
            },
            success: function(response) {
                var comments = document.createElement('div');
                comments.innerHTML = response;
                comments = comments.querySelector('#comments-list');
                comments = comments.children;

                while (comments.length) {
                    comments[0].classList.add('fadeInUp','animated');
                    $commentListContainer.appendChild(comments[0]);
                }

                $loadMoreButton.dataset['currentPage'] = ++currentPage;
                if (currentPage >= totalPages) {
                    $loadMoreButton.classList.add('is-hidden');
                }
            },
            complete: function() {
                isLoadingMore = false;
                self.classList.remove('infinite', 'animated', 'flash');
            }
        });
    });

    /**
     * COMMENTS ORDER
     */
    CoolSelect($commentsSort, {
        class: $commentsSort.className
    });

    addEvent($commentsSort, 'change', function()
    {
        var uri = modifyQueryParams({order: this.value, s: ''});
        loadComments(uri);
    });

    /**
     * COMMENTS BULK ACTIONS
     */
    var $commentForm = document.getElementById('comments-form');
    addEvent('.quick-action-buttons span', 'click', function()
    {
        var data = $commentForm.serialize(),
            action = this.dataset['action'];
        commentAction(action, data['comments'], 'bulk');
    });

    function commentAction(action, comments, type)
    {
        type = type || 'single';
        ajax(ajaxurl, {
            json: true,
            data: {
                action: 'comment-moderate',
                sub_action: action,
                comments: comments,
                type: type
            },
            success: function(response) {
                if (!response.success) {
                    return;
                }
                var $comment, $container, $undo;

                if (response['message']) {
                    $undo = document.createElement('div');
                    $comment = document.getElementById('comment-' + comments[0]);
                    $comment.classList.add('is-temp');
                    $container = $comment.querySelector('.comment-outer');
                    $undo.innerHTML = response['message'];
                    $undo = $undo.firstElementChild;
                    $container.appendChild($undo);
                } else {
                    for (var i = comments.length; i--;) {
                        $comment = document.getElementById('comment-' + comments[i]);

                        if ($comment.classList.contains('is-temp')) {
                            $undo = $comment.querySelector('.undo');
                            $undo.parentNode.removeChild($undo);
                            $comment.classList.remove('is-temp');
                        } else {
                            $comment.parentNode.removeChild($comment);
                        }
                    }
                }
                updateCommentsCount();
            }
        });
    }

    /**
     * SINGLE COMMENT ACTIONS
     */

    $commentsContainer.addListener('click', '.comment-menu a, .undo-link', function(e) {
        e.preventDefault();

        var comment = this.parents('.comment'),
            id = comment.dataset['id'],
            action = this.dataset['action'];

        if (action === 'edit' || action === 'reply') {
            addCommentForm(comment, action);
            return;
        }
        commentAction(action, [id]);
    });

    addEvent('.comment-form .cancel-btn', 'click', function() {
        var $comment = this.parents(commentClass);
        $comment && hideCommentForm($comment);
    });

    addEvent('.save-btn', $editForm, 'click', function(e) {
        e.preventDefault();
        var $comment = this.parents(commentClass);
        updateComment($comment);
    });

    addEvent($replyForm, 'submit', function(e) {
        e.preventDefault();
        var formData = $replyForm.serialize(),
            $comment = this.parents(commentClass);
        formData['is_admin'] = true;

        ajax(this.action, {
            method: 'post',
            data: formData,
            json: true,

            beforeSend: function() {
                $comment.classList.add('has-spinner', 'pos-middle');
            },
            success: function(response) {
                if (response.success) {
                    var parent = formData['comment_parent'];

                    // Create comment DOM
                    var comment = document.createElement('div');
                    comment.innerHTML = response.content;
                    comment = comment.firstElementChild;

                    var list = document.getElementById('comments-list');
                    list.insertBefore(comment, list.firstElementChild);
                    comment.classList.add('animated', 'fadeIn');
                    hideCommentForm($comment);
                } else if (response.error) {
                    //Errors.add(response.error);
                }
            },
            error: function(xhr) {
                console.error('Comment form - %s (%s)', xhr.status, xhr.statusText);
            },
            complete: function() {
                $comment.classList.remove('has-spinner', 'pos-middle');
            }
        })
    });

    function updateComment($comment)
    {
        ajax(ajaxurl, {
            json: true,
            method: 'post',
            data: {
                action: 'comment-update',
                id: $comment.dataset['id'],
                content: $editEditor.value
            },
            beforeSend: function() {
                $comment.classList.add('has-spinner', 'pos-middle');
            },
            success: function(response) {
                if (response.success) {
                    $comment.querySelector('.comment-body').innerHTML = response.content;
                    hideCommentForm($comment);
                }
            },
            complete: function() {
                $comment.classList.remove('has-spinner', 'pos-middle');
            }
        })
    }

    function addCommentForm($comment, type)
    {
        if (!$comment) {
            return;
        }
        type = type || 'edit';

        var localEditor = type === 'edit' ? $editEditor : $replyEditor,
            form = type === 'edit' ? $editForm : $replyForm;

        var $commentWithForm = $commentsContainer.querySelector(commentClass + '.has-comment-form');
        if ($commentWithForm) {
            $commentWithForm.querySelector('.comment-body').classList.remove('is-hidden');
            $commentWithForm.classList.remove('has-comment-form');
        }
        $comment.classList.add('has-comment-form');
        var $content = $comment.querySelector('.comment-body');
        $content.parentNode.insertBefore(form, $content.nextElementSibling);

        if (type !== 'reply') {
            $content.classList.add('is-hidden');
            localEditor.value = $content.innerHTML.trim()
        } else {
            $replyForm.elements['comment_post_ID'].value = $comment.dataset['postId'];
            $replyForm.elements['comment_parent'].value = $comment.dataset['parent'];
        }
        autoSize(localEditor);
    }

    function hideCommentForm($comment)
    {
        $comment = $comment || $commentsContainer.querySelector(commentClass + '.has-comment-form');
        if (!$comment || !$comment.classList.contains('has-comment-form')) {
            return;
        }
        $editEditor.value = '';
        $replyEditor.value = '';
        $replyForm.elements['comment_post_ID'].value = '';
        $replyForm.elements['comment_parent'].value = '';
        $comment.classList.remove('has-comment-form');
        $comment.querySelector('.comment-body').classList.remove('is-hidden');
        var $form = $comment.querySelector('.comment-form');
        $commentListContainer.parentNode.insertBefore($form, $commentListContainer.nextElementSibling);
    }

    function autoSize($txtArea)
    {
        $txtArea.addEventListener('input', function ()
        {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    }

    /**
     * UPDATE COMMENTS COUNT
     */
    var $tabs = document.querySelectorAll('.nav-tab');
    function updateCommentsCount()
    {
        ajax(ajaxurl + '?action=' + 'comments-count', {
            json: true,
            success: function(response) {
                if (!response['count']) {
                    return;
                }
                var $count, num;

                for (var i = $tabs.length; i--;) {
                    $count = $tabs[i].querySelector('.count');
                    num = response['count_i18n'][$tabs[i].dataset['status']];
                    num && ($count.innerText = '(' + num + ')');
                }
            }
        })
    }

    /**
     * COMMENT REPLY
     */




    addEvent('#comments-form', 'submit', function(e)
    {
        e.preventDefault();
    });

});