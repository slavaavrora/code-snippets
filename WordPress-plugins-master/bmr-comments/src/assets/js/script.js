addEvent(window, 'DOMContentLoaded', function()
{
    "use strict";

    var $commentsContainer,
        $pinnedCommentContainer,
        $commentForm,
        $commentsSort,
        $commentFormContainer,
        $commentFormParentField,
        $commentFormAttachmentsField,
        isUserLoggedIn,
        $commentListContainer,
        commentClass,
        $modMenu,
        $tabPane,
        modMenuOpen,
        $fileInput,
        $attachmentsContainer,
        $loadMoreButton,
        $editorContainer,
        $tabs,
        $editor,
        Errors
        ;

    $commentListContainer   = document.getElementById('comments-list');
    $commentForm            = document.forms['comment-form'];

    if (!$commentListContainer || !$commentForm) {
        return;
    }

    $commentsContainer = document.getElementById('comments');
    $pinnedCommentContainer = document.getElementById('pinned-comment');
    $commentsSort           = document.getElementById('comments-sort');
    $commentFormContainer   = document.querySelector('.comment-form-container');
    $commentFormParentField = $commentForm.elements['comment_parent'];
    isUserLoggedIn          = document.body.classList.contains('logged-in');
    commentClass            = '.bmr-comment';
    $modMenu                = document.getElementById('comment-moderation-menu');
    $tabPane                = $commentListContainer.parents('.tab-pane');
    modMenuOpen             = false;
    $fileInput              = document.getElementById('file');
    $attachmentsContainer   = $commentForm.querySelector('.attachments-preview');
    $commentFormAttachmentsField = $commentForm.elements['comment_attachment'];
    $loadMoreButton         = document.getElementById('comments-load-more');
    $editorContainer        = document.getElementById('edit-comment');
    $editor                 = document.getElementById('editcontent');
    $tabs                   = $commentsContainer.querySelector('.comments-tabs');

    if (!$tabs) {
        return;
    }

    window.VK && VK.Api.call('widgets.getComments', {
        widget_api_id: bmrComments['options']['vk_id'], // dev: 4737667 | live: 4888748
        url: location.href.replace(/[#?].*$/, '')
    }, function(response)
    {
        updateCommentsCount(response.response.count, 'vk');
    });

    var FormError = function()
    {
        var $errorsContainer = $commentFormContainer.querySelector('.summary');

        this.add = function(message, type, group)
        {
            group = group || 'common';
            type  = type  || 'error';
            var el = document.createElement('span');
            el.classList.add(type);
            el.classList.add(group + '-error-group');
            el.innerHTML = message;
            $errorsContainer.appendChild(el);
            el.classList.add('animated', 'fadeIn');
            $errorsContainer.classList.remove('is-hidden');
        };

        this.clear = function(group)
        {
            group = group || 'common';

            if (group === 'common') {
                $errorsContainer.innerHTML = '';
            } else {
                var errors = $errorsContainer.querySelectorAll('.' + group  + '-error-group');
                for (var i = errors.length; i--;) {
                    $errorsContainer.removeChild(errors[i]);
                }
            }
        };
    };
    Errors = new FormError();

    var Attachments = {
        add: function(id) {
            var data = $commentFormAttachmentsField.value;

            if (data === '') {
                data = [];
            } else {
                data = data.split(',');
            }

            data.push(id);
            data = data.join(',');
            $commentFormAttachmentsField.value = data;
        },
        remove: function(id) {
            var data = $commentFormAttachmentsField.value,
                index;
            data = data.split(',');

            if (index = data.indexOf(id.toString()) !== -1) {
                data = data.splice(index, 1).join(',');
                $commentFormAttachmentsField.value = data;
            }
        }
    };

    var ProgressBar = function(size)
    {
        size = size || 50;

        var $canvas,
            ctx,
            lineWidth,

        // init
        $canvas         = document.createElement('canvas');
        $canvas.height  = size;
        $canvas.width   = size;
        $canvas.className = 'img-upload-progress';
        ctx             = $canvas.getContext('2d');
        lineWidth       = size / 2;
        ctx.translate(lineWidth, lineWidth);
        ctx.rotate(1.5 * Math.PI);
        ctx.font = "normal 11px GothamBold";
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";

        function paintArc(r, startDeg, endDeg, fillColor)
        {
            ctx.beginPath();
            ctx.arc(0, 0, r, startDeg, endDeg, false);
            ctx.rotate(endDeg);
            ctx[(endDeg - startDeg) !== 2 * Math.PI ? 'lineTo' : 'moveTo'](0, 0);
            ctx.rotate(-endDeg);
            ctx.arc(0, 0, 0, endDeg, startDeg, true);
            ctx.closePath();
            ctx.fillStyle = fillColor;
            ctx.fill();
        }

        function draw(percents)
        {
            ctx.clearRect(-lineWidth, -lineWidth, size, size);
            paintArc(lineWidth, 0, 2 * Math.PI * percents / 100, "#3498DB");
            paintArc(lineWidth - 7, 0, 2 * Math.PI, "#3D4750");
            ctx.fillStyle = "#fff";
            ctx.rotate(-1.5 * Math.PI);
            ctx.fillText(percents + '%', 0, 0);
            ctx.rotate(1.5 * Math.PI);
        }

        function getElement()
        {
            return $canvas;
        }

        this.draw = draw;
        this.getElement = getElement;
    };

    /**
     * Collect current comments ids
     * @type {Array}
     */
    var commentsIds = [];
    function collectComments()
    {
        var comments = $commentListContainer.querySelectorAll(commentClass);
        commentsIds = [];
        if (!comments.length) {
            return;
        }
        for(var i = comments.length; i--;) {
            commentsIds.push(comments[i].dataset['id']);
        }
        commentsIds.sort();
        commentsIds = commentsIds.join(',');
    }
    collectComments();

    /**
     * Check for new comments
     */
    var checkingForNewComments = false;
    function checkComments()
    {
        if (commentsIds.length === 0) {
            return;
        }
        ajax(bmrComments.ajaxUrl, {
            json: true,
            data: {
                action: 'comments-check-new',
                post_id: $commentForm.elements['comment_post_ID'].value,
                comment_ids: commentsIds,
                comment_order_by: $commentsSort.value
            },
            beforeSend: function() {
                checkingForNewComments = true;
            },
            success: function(response) {
                if (response.success) {
                    var comment = document.createElement('div'), comments = response.content;

                    for (var parentId in comments) {
                        if (!comments.hasOwnProperty(parentId)) {
                            continue;
                        }
                        for (var i = comments[parentId].length; i--;) {
                            comment.innerHTML = comments[parentId][i];
                            comment = comment.firstChild;
                            comment.firstElementChild.classList.add('is-hidden');
                            insertComment(comment, parentId);
                        }
                        addNewCommentsTip(parentId, comments[parentId].length);
                    }
                    collectComments();
                }
            },
            complete: function() {
                checkingForNewComments = false;
            }
        });
    }

    /**
     * Add tip about new comments count
     * @param parent comment id
     * @param count
     */
    function addNewCommentsTip(parent, count)
    {
        var $comment = document.getElementById('comment-' + parent), $tip;

        if (!$comment && parent != 0) {
            return;
        }

        if (parent != 0) {
            do {
                $comment = $comment.parentNode.parentNode.previousElementSibling;
            } while ($comment.dataset['parent'] != 0);
            parent = $comment.dataset['id'];
        }

        if (!($tip = document.getElementById('comment-tip-' + parent))) {
            $tip = document.createElement('span');
            $tip.id = 'comment-tip-' + parent;
            $tip.className = 'new-comments-tip children';
            $tip.dataset['count'] = count;
            $tip.textContent = count + ' ' + pluralForm(count, bmrComments['i18n']['new_comments_plural']);

        } else if (count != 0) {
            $tip.dataset['count'] = parseInt($tip.dataset['count']) + parseInt(count);
            $tip.textContent = $tip.dataset['count'] + ' ' + pluralForm($tip.dataset['count'], bmrComments['i18n']['new_comments_plural']);
            return;
        } else {
            $tip.parentNode.removeChild($tip);
            return;
        }

        if (parent == 0) {
            $tip.classList.remove('children');
            $commentListContainer.insertBefore($tip, $commentListContainer.firstChild);
        } else {
            $comment.parentNode.appendChild($tip);
        }
    }

    window.at = addNewCommentsTip;

    function getCommentsCount(type)
    {
        type = type || 'native';
        var $counter = $tabs.querySelector('[data-tab="' + type + '"] .comments-count');
        return $counter ? parseInt($counter.textContent) : false;
    }

    function updateCommentsCount(value, type)
    {
        type = type || 'native';
        value = parseInt(value);

        var $counter = $tabs.querySelector('[data-tab="' + type + '"] .comments-count');
        !isNaN(value) && $counter && ($counter.textContent = value);
    }

    /**
     * Add tip about new comments
     */
    $commentListContainer.addListener('click', '.new-comments-tip', function()
    {
        var id = this.id.match(/\d+/)[0], hidden, currentCount;
        hidden = $commentListContainer.querySelectorAll('.bmr-comment.is-hidden[data-parent="' + id + '"]');

        for (var i = 0; i < hidden.length; i++) {
            hidden[i].classList.remove('is-hidden');
        }

        currentCount = getCommentsCount();
        updateCommentsCount(currentCount + hidden.length);
        this.parentNode.removeChild(this);
    });

    /**
     * Check for new comments every 10 minutes
     */
    setInterval(function() {
        if (checkingForNewComments) {
            return;
        }
        checkComments();
    }, 1000 * 60 * 10);

    /**
     * LOAD MORE
     */
    var initialPage = $loadMoreButton ? $loadMoreButton.dataset['currentPage'] : 1;

    +function() {
        var total, current, isLoadingMore = false;

        if (!$loadMoreButton) {
            return;
        }

        total = $loadMoreButton.dataset['totalPages'];
        current = initialPage;

        addEvent($loadMoreButton, 'click', function()
        {
            if (this.classList.contains('is-hidden') || isLoadingMore) {
                return;
            }
            var self = this;
            current = this.dataset['currentPage'];
            ajax(bmrComments.ajaxUrl, {
                data: {
                    action: 'comments-load-more',
                    comment_order_by: $commentsSort.value,
                    current: current,
                    post_id: $commentForm.elements['comment_post_ID'].value
                },
                beforeSend: function() {
                    self.classList.add('infinite', 'animated', 'flash');
                    isLoadingMore = true;
                },
                success: function(response) {
                    var comments = document.createElement('div'), contentBlocks;
                    comments.innerHTML = response;
                    contentBlocks = comments.querySelectorAll('.comment-content');
                    comments = comments.children;

                    while (comments.length) {
                        comments[0].classList.add('animated', 'fadeInUp');
                        $commentListContainer.appendChild(comments[0]);
                    }

                    collectComments();
                    collapseCommentsContentByHeight(contentBlocks);

                    $loadMoreButton.dataset['currentPage'] = ++current;
                    if (current >= total) {
                        $loadMoreButton.classList.add('is-hidden');
                    }
                },
                complete: function() {
                    self.classList.remove('infinite', 'animated', 'flash');
                    isLoadingMore = false;
                }
            });
        });
    }();

    /**
     * COMMENTS SORT
     */
    CoolSelect($commentsSort, {
        class: $commentsSort.className
    });

    //setTimeout(function() {
    //    $commentsSort.triggerEvent('change');
    //}, 200);

    addEvent($commentsSort, 'change', function()
    {
        var spinner = document.createElement('div');
        spinner.className = 'bmr-loading-spinner';
        ajax(bmrComments.ajaxUrl, {
            data: {
                action: 'comments-load-more',
                comment_order_by: this.value,
                current: 0,
                post_id: $commentForm.elements['comment_post_ID'].value
            },
            beforeSend: function() {
                returnForm();
                hideEditForm();
                $commentListContainer.appendChild(spinner);
            },
            success: function(response) {
                $commentListContainer.innerHTML = response;
                spinner = false;
                $loadMoreButton.dataset['currentPage'] = initialPage;
                //$loadMoreButton.classList.remove('is-hidden');
                collectComments();
            },
            complete: function() {
                spinner && $commentListContainer.removeChild(spinner);
            }
        })
    });

    /**
     * ATTACHMENT UPLOAD
     */
    addEvent($fileInput, 'change', function(e)
    {
        e.preventDefault();

        if (!this.files.length) {
            return;
        }

        var file = this.files[0],
            previewItem = document.createElement('div'),
            progressBar = new ProgressBar();

        previewItem.className = 'preview-item';
        previewItem.appendChild(progressBar.getElement());
        progressBar.draw(0);

        $attachmentsContainer.appendChild(previewItem);
        $attachmentsContainer.classList.remove('is-hidden');

        Errors.clear('attachment');

        checkFile(file).then(function(src) {
        uploadFile(file, src, previewItem, progressBar);
    }, function(error) {
        previewItem.parentNode.removeChild(previewItem);
        !$attachmentsContainer.children.length && $attachmentsContainer.classList.add('is-hidden');
        Errors.add(error, null, 'attachment');

    }).catch(function(error) {
        previewItem.parentNode.removeChild(previewItem);
        !$attachmentsContainer.children.length && $attachmentsContainer.classList.add('is-hidden');
        console.error('Attachment upload: ' + error);
        Errors.add(bmrComments['errors']['unknown'], null, 'attachment');
    });
    });

    /**
     * Check file
     * @param file
     * @returns {*}
     */
    function checkFile(file)
    {
        var allowedTypes = ['jpg', 'jpe', 'jpeg', 'gif', 'png', 'bmp'],
            maxFileSize = 2 * 1024 * 1024,
            reader = new FileReader(),
            image = new Image();

        return new Promise(function(resolve, reject)
        {
            reader.addEventListener('loadend', function() {
                image.src = reader.result;
            });
            image.addEventListener('load', function() {
                var type = file.type.split('/')[1],
                    name = file.name,
                    size = file.size;

                if (allowedTypes.indexOf(type) === -1) {
                    reject(bmrComments['errors']['file_type']);
                }
                if (size > maxFileSize) {
                    reject(bmrComments['errors']['file_size']);
                }
                resolve(this.src);
            });
            image.addEventListener('error', function() {
                reject(bmrComments['errors']['file_type']);
            });
            reader.readAsDataURL(file);
        });
    }

    /**
     * Upload file
     * @param file file
     * @param src preview image src
     * @param previewItem preview item container
     * @param progressBar progress bar
     */
    function uploadFile(file, src, previewItem, progressBar)
    {
        var formData = new FormData();
        formData.append('action', 'comment-upload-attachment');
        formData.append("file", file);
        formData.append('_token', bmrComments['tokens']['comment-upload-attachment']);

        ajax(bmrComments.ajaxUrl, {
            method: "POST",
            json: true,
            data: formData,
            beforeSend: function() {
                // set preview image
                previewItem.style.backgroundImage = 'url(' + src + ')';
                progressBar.draw(1);
            },
            success: function(response) {
                if (response.success) {
                    // update preview image on load
                    var image = new Image();
                    image.addEventListener('load', function imgLoaded() {
                        previewItem.style.backgroundImage = 'url(' + response.data['url'] + ')';
                        image.removeEventListener('load', imgLoaded);
                    });
                    image.src = response.data['url'];

                    // add remove button
                    var removeBtn = document.createElement('i');
                    removeBtn.className = 'icon-close';
                    previewItem.appendChild(removeBtn);

                    setTimeout(function() {
                        previewItem.classList.add('is-ready');
                    }, 200);

                    // add image post id
                    previewItem.dataset['imgId'] = response.data['id'];
                    Attachments.add(response.data['id']);
                } else {
                    if (response.error) {
                        Errors.add(response.error, null, 'attachment');
                    }
                }
            },
            progress: function(evt) {
                if (evt.lengthComputable) {
                    var percentComplete = (evt.loaded / evt.total)*100;
                    progressBar.draw(parseInt(percentComplete));

                    if (percentComplete >= 100) {
                        progressBar.draw(100);
                    }
                }
            }
        });
    }

    /**
     * Delete file
     */
    $attachmentsContainer.addListener('click', '.icon-close', function()
    {
        var previewItem = this.parentNode, attachId;
        if (!previewItem.dataset['imgId']) {
            return;
        }
        attachId = previewItem.dataset['imgId'];

        ajax(bmrComments.ajaxUrl, {
            json: true,
            data: {
                action: 'comment-delete-attachment',
                id: attachId,
                _token: bmrComments['tokens']['comment-delete-attachment']
            },
            beforeSend: function() {
                previewItem.classList.add('is-removing');
                Errors.clear('attachment-delete');
            },
            success: function(response) {
                if (response.success) {
                    previewItem.classList.add('is-deleted');
                    setTimeout(function() {
                        previewItem.parentNode.removeChild(previewItem);
                        !$attachmentsContainer.children.length && $attachmentsContainer.classList.add('is-hidden');
                    }, 200);
                    Attachments.remove(attachId);
                }
            },
            complete: function() {
                if (previewItem && !previewItem.classList.contains('is-deleted')) {
                    previewItem.classList.remove('is-removing');
                    Errors.add(bmrComments['errors']['file_delete'], null, 'attachment-delete');
                }
            }
        });
    });

    function resetForm()
    {
        $attachmentsContainer.classList.add('is-hidden');
        $attachmentsContainer.innerHTML = '';
        $commentForm.elements['comment_parent'].value = 0;
        $commentForm.elements['comment_attachment'].value = '';
        $commentForm.elements['message'].value = '';

        try {
            $commentForm.elements['file'].value = '';
        } catch(error) {
            var form = document.createElement('form'),
                parentNode = $commentForm.parentNode, ref = $commentForm.nextSibling;
            form.appendChild($commentForm);
            form.reset();
            parentNode.insertBefore(f,ref);
        }
    }

    /**
     * Moderation menu popup
     */
    $commentListContainer.addListener('click', '.moderation', function()
    {
        var tabOffsetY = $tabPane ? bmr.getPosition($tabPane).y : 0,
            curOffsetY = bmr.getPosition(this).y,
            opened = $commentListContainer.querySelector('.moderation.open'),
            $comment = this.parents(commentClass);

        if (!$comment) {
            return;
        }

        $modMenu.style.top = (curOffsetY - tabOffsetY) + 15 + 'px';
        opened && opened !== this && opened.classList.remove('open');
        this.classList.toggle('open');
        modMenuOpen = this.classList.contains('open') ? this : false ;

        if (modMenuOpen) {
            $modMenu.dataset['currentComment'] = $comment.dataset['id'];
            $modMenu.dataset['commentStatus'] = $comment.dataset['status'];
            $modMenu.classList.remove('is-hidden');
        } else {
            $modMenu.dataset['currentComment'] = '';
            $modMenu.dataset['commentStatus'] = '';
            $modMenu.classList.add('is-hidden');
        }
    });

    $commentsContainer.addListener('click', '.undo-link', function(e)
    {
        e.preventDefault();
        var $comment = this.parents(commentClass);
        commentAction($comment, this.dataset['action']);
    });

    $modMenu && $modMenu.addListener('click', 'a', function(e)
    {
        e.preventDefault();
        if (!$modMenu.dataset['currentComment']) {
            return;
        }

        var action = this.dataset['action'],
            $comment = document.getElementById('comment-' + $modMenu.dataset['currentComment']);

        if ($comment.parentNode.classList.contains('is-collapsed')) {
            $comment.parentNode.classList.remove('is-collapsed');
            $comment.parentNode.style.overflow = '';
            $comment.parentNode.style.height = '';
        }

        if (action === 'edit') {
            //this.href = this.href.replace('{id}', $modMenu.dataset['currentComment']);
            //window.open(this.href, "_blank");
            addEditForm($comment);
            return;
        }
        commentAction($comment, action);
    });

    addEvent('#edit-comment .cancel-btn', 'click', function() {
        var $comment = this.parents(commentClass);
        $comment && hideEditForm($comment);
    });

    addEvent('#edit-comment .save-btn', 'click', function() {
        var $comment = this.parents(commentClass);

        ajax(bmrComments.ajaxUrl, {
            json: true,
            method: 'post',
            data: {
                action: 'comment-update',
                id: $comment.dataset['id'],
                content: $editor.value
            },
            beforeSend: function() {
                $comment.classList.add('has-bmr-spinner', 'pos-middle');
            },
            success: function(response) {
                if (response.success) {
                    $comment.querySelector('.comment-content').innerHTML = response.content;
                    hideEditForm($comment);
                }
            },
            complete: function() {
                $comment.classList.remove('has-bmr-spinner', 'pos-middle');
            }
        })
    });

    function addEditForm($comment)
    {
        if (!$comment) {
            return;
        }
        var $commentWithForm = $commentsContainer.querySelector(commentClass + '.has-edit-form');
        if ($commentWithForm) {
            $commentWithForm.querySelector('.comment-content').classList.remove('is-hidden');
            $commentWithForm.classList.remove('has-edit-form');
        }
        $comment.classList.add('has-edit-form');
        var $content = $comment.querySelector('.comment-content');
        $editor.style.height = $content.offsetHeight + 'px';
        $content.parentNode.insertBefore($editorContainer, $content.nextElementSibling);
        $content.classList.add('is-hidden');
        $editor.value = $content.innerHTML.trim();
        autoSize($editor);
    }

    function hideEditForm($comment)
    {
        $comment = $comment || $commentsContainer.querySelector(commentClass + '.has-edit-form');
        if (!$comment || !$comment.classList.contains('has-edit-form')) {
            return;
        }
        $comment.classList.remove('has-edit-form');
        $comment.querySelector('.comment-content').classList.remove('is-hidden');
        $commentListContainer.parentNode.insertBefore($editorContainer, $commentListContainer.nextElementSibling);
    }

    function autoSize($txtArea)
    {
        if (!$txtArea.dataset['minSize']) {
            $txtArea.style.minHeight = $txtArea.offsetHeight + 'px';
            $txtArea.dataset['minSize'] =  $txtArea.offsetHeight;

            $txtArea.addEventListener('input', function ()
            {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        } else if ($txtArea.offsetHeight != $txtArea.dataset['minSize']) {
            $txtArea.style.minHeight = $txtArea.offsetHeight + 'px';
            $txtArea.dataset['minSize'] = $txtArea.offsetHeight;
        }
    }

    /**
     * Executes comment action
     * @param $comment
     * @param action
     */
    function commentAction($comment, action)
    {
        ajax(ajaxurl, {
            json: true,
            data: {
                action: 'comment-moderate',
                sub_action: action,
                comments: [$comment.dataset['id']],
                type: 'single'
            },
            success: function(response) {
                if (!response.success) {
                    return;
                }

                if (['trash', 'spam', 'blacklist', 'pin'].indexOf(action) !== -1) {
                    addUndoBlock($comment, response['message']);
                    $comment.dataset['status'] = action + 'ed';
                }

                if (['untrash', 'unspam', 'unblacklist', 'unpin'].indexOf(action) !== -1) {
                    addUndoBlock($comment, null);
                    $comment.classList.remove('is-temp');
                    $comment.dataset['status'] = '';
                }

                if (['trash', 'spam'].indexOf(action) !== -1) {
                    $comment.classList.add('is-temp');
                }

                var $pinnedComment;
                if (action === 'pin') {
                    $pinnedComment = $pinnedCommentContainer.querySelector(commentClass);
                    $pinnedComment && $pinnedCommentContainer.removeChild($pinnedComment);

                    if ($pinnedComment) {
                        var $toUnpin = document.getElementById('comment-' + $pinnedComment.dataset['id']);
                        $pinnedCommentContainer.removeChild($pinnedComment);

                        if ($toUnpin) {
                            $toUnpin.classList.remove('is-pinned');
                            $toUnpin.dataset['status'] = '';
                        }
                    }
                    $comment.classList.add('is-pinned');
                    var $clone = $comment.cloneNode(true);
                    $clone.id = '';
                    $pinnedCommentContainer.appendChild($clone);
                    $pinnedCommentContainer.classList.remove('is-hidden');
                } else if (action === 'unpin') {
                    $comment.classList.remove('is-pinned');
                    $pinnedComment = $pinnedCommentContainer.querySelector(commentClass);
                    $pinnedCommentContainer.removeChild($pinnedComment);
                    $pinnedCommentContainer.classList.add('is-hidden');
                }
            }
        });
    }

    /**
     * Add undo block to comment
     * @param $comment
     * @param message pass null to remove undo block
     */
    function addUndoBlock($comment, message) {
        var $undo;
        if (!message || message.length === 0) {
            $undo = $comment.querySelector('.undo');
            $undo && $undo.parentNode.removeChild($undo);
        } else {
            $undo = document.createElement('div');
            $undo.innerHTML = message;
            $undo = $undo.firstElementChild;
            $comment.appendChild($undo);
            $undo.classList.add('animated', 'fadeIn');
        }
    }

    /**
     * Close comment moderaion menu on click anywhere else
     */
    document.addEventListener('click', function(e)
    {
        if (!modMenuOpen || modMenuOpen === e.target /*|| (e.target.href && e.target.href.match('comment.php'))*/) {
            return;
        }
        modMenuOpen.classList.remove('open');
        $modMenu.classList.add('is-hidden');
        $modMenu.dataset['currentComment'] = '';
        modMenuOpen = false;
    });

    /**
     * Comments tabs
     */
    addEvent('.comments-tabs .tabs-nav [data-tab]', 'click', function()
    {
        var i,
            $navTabs = this.parentNode.children;

        for (i = $navTabs.length; i--; ) {
            $navTabs[i].classList[this.dataset.tab === $navTabs[i].dataset.tab ? 'add' : 'remove']('active');
        }

        var $contentTabs = this.parentNode.nextElementSibling.nextElementSibling.children;
        for (i = $contentTabs.length; i--; ) {
            $contentTabs[i].classList[this.dataset.tab === $contentTabs[i].dataset.tab ? 'add' : 'remove']('active');
        }
        this.parents('.comments-tabs').dataset['currentTab'] = this.dataset.tab;
    });

    $commentsContainer.addListener('click', '.comment-likes a', function(e)
    {
        e.preventDefault();
        var self = this,
            $comment = this.parents(commentClass),
            $like = this.parentNode.querySelector('.like'),
            $dislike = this.parentNode.querySelector('.dislike');

        ajax(bmrComments.ajaxUrl, {
            json: true,
            data: {
                action: 'comment-rate',
                id: $comment.dataset['id'],
                type: self.classList.contains('like')
            },
            success: function(response) {
                if (!response.success) {
                    return;
                }

                !response['likes'].hasOwnProperty('-1') && (response['likes']['-1'] = 0);
                !response['likes'].hasOwnProperty('1') && (response['likes']['1'] = 0);

                // Set active state to rate button
                var $active = self.parentNode.querySelector('.active');
                $active && $active.classList.remove('active');
                self.classList[$active && self == $active ? 'remove' : 'add']('active');

                // Set like and dislike counters
                $dislike.innerHTML = '<i class="likes-count">' + response['likes']['-1'] + '</i>';
                $like.innerHTML = '<i class="likes-count">' + response['likes']['1'] + '</i>';
            }
        });
    });

    /**
     * Form trigger button click handler
     */
    addEvent('.comment-form-container.form-is-hidden', 'click', openForm);

    /**
     * Comment answer button click handler
     */
    $commentListContainer.addListener('click', '.comment-answer', function(e)
    {
        e.preventDefault();
        var $comment = this.parents(commentClass);

        if (!isUserLoggedIn) {
            openForm();
            return;
        }

        if ($comment.dataset['id'] === $commentFormParentField.value) {
            returnForm();
            return;
        }

        // Move form to new place
        $commentFormParentField.value = $comment.dataset['id'];
        $comment.parentNode.insertBefore($commentFormContainer, $comment.nextSibling);

        setTimeout(function() {
            openForm();
        }, 100);
    });

    addEvent($commentForm, 'submit', function(e)
    {
        e.preventDefault();
        var formData = this.serialize(),
            spinner = $commentFormContainer.querySelector('.bmr-loading-spinner');

        ajax(this.action, {
            method: 'POST',
            data: formData,
            json: true,

            beforeSend: function() {
                Errors.clear();
                spinner.style.display = 'block';
            },

            success: function(response) {
                if (response.success) {
                    var parent = formData['comment_parent'];

                    // Create comment DOM
                    var comment = document.createElement('div');
                    comment.innerHTML = response.content + '</li>';
                    comment = comment.firstChild;

                    returnForm();
                    resetForm();

                    // Insert comment
                    insertComment(comment, parent);

                    collectComments();
                } else if (response.error) {
                    Errors.add(response.error);
                }
            },

            error: function(xhr) {
                Errors.add(bmrComments['errors']['unknown']);
                console.error('Comment form - %s (%s)', xhr.status, xhr.statusText);
            },
            complete: function() {
                spinner.style.display = 'none';
            }
        });
    });

    function insertComment(comment, parent)
    {
        if (parent == 0) {
            $commentListContainer.insertBefore(comment, $commentListContainer.firstChild);
        } else {
            var parentComment = document.getElementById('comment-' + parent),
                childrenContainer;

            if (!(childrenContainer = parentComment.querySelector('.children'))) {
                childrenContainer = document.createElement('ul');
                childrenContainer.className = 'children';
                parentComment.parentNode.appendChild(childrenContainer);
            }
            childrenContainer.appendChild(comment);
        }
        comment.classList.add('animated', 'fadeIn');
    }

    /**
     * Show comment form
     */
    function openForm() {
        if (!isUserLoggedIn) {
            bmrAuth && bmrAuth.show('auth', 'Что бы оставлять комментарии зарегистрируйтесь или войдите на сайт');
        } else {
            $commentFormContainer.classList.remove('form-is-hidden');
            setTimeout(function() {
                autoSize(document.getElementById('message'));
            }, 305);
        }
    }

    function returnForm()
    {
        if ($commentFormParentField.value != 0) {
            $commentFormParentField.value = 0;
            $commentListContainer.parentNode.insertBefore($commentFormContainer, $commentListContainer);
        }
    }

    /**
     * Set comment block as target
     * @param hash
     */
    function setTarget(hash)
    {
        var el = document.getElementById(hash.substr(1)),
            targeted = document.querySelector(commentClass + '.target');

        if (!el || (el && el.classList.contains('target'))) {
            return;
        }

        if (location.hash !== hash) {
            history.pushState ? history.pushState(null, null, hash) : (location.hash = hash);
        }
        targeted && targeted.classList.remove('target');
        el.classList.add('target');
    }

    /**
     * If hash is set, try to set target on comment block
     */
    if (location.hash) {
        setTarget(location.hash);
    }

    /**
     * Comment social buttons click handler
     */
    $commentListContainer.addListener('click', '.comment-socials .comment-link', function(e)
    {
        e.preventDefault();

        if (location.hash !== this.hash) {
            setTarget(this.hash);
        }
    });


    /**
     * Toggle comment
     */
    $commentListContainer.addListener('click', '.toggle', function()
    {
        var $comment = this.parents('li'),
            commentHeight = $comment.offsetHeight,
            isCollapsed = $comment.classList.contains('is-collapsed');

        !isCollapsed && ($comment.style.height = commentHeight + 'px');
        $comment.style.overflow = 'hidden';

        $comment.classList.toggle('is-collapsed');
        if (isCollapsed) {
            $comment.style.overflow = '';
            $comment.style.height = '';
        }
    });

    /**
     * Collapse comment content by specific height with bottom gradient
     * @param contentBlocks
     */
    function collapseCommentsContentByHeight(contentBlocks)
    {
        contentBlocks = contentBlocks || $commentListContainer.querySelectorAll('.comment-content');
        var height = bmrComments['options']['content_height'];

        for (var i = contentBlocks.length; i--;) {
            if (contentBlocks[i].offsetHeight > height) {
                if (contentBlocks[i].classList.contains('is-collapsed')) {
                    continue;
                }
                contentBlocks[i].classList.add('is-collapsed');
                contentBlocks[i].style.height = height + 'px';

                var revealButton = document.createElement('div');
                revealButton.classList.add('comment-reveal-button');
                revealButton.innerHTML = bmrComments['i18n']['reveal-button-text'];

                contentBlocks[i].parentNode.insertBefore(revealButton, contentBlocks[i].nextElementSibling);
            } else if (contentBlocks[i].classList.contains('is-collapsed')) {
                revealCommentContent.call(contentBlocks[i].nextElementSibling);
            }
        }
    }
    collapseCommentsContentByHeight();
    $commentListContainer.addListener('click', '.comment-reveal-button', revealCommentContent);

    function revealCommentContent()
    {
        var $content = this.previousElementSibling;
        $content.classList.remove('is-collapsed');
        $content.style.height = '';
        this.parentNode.removeChild(this);
    }

    var lastWidth = window.innerWidth;
    addEvent(window, 'resize', function() {
        if (lastWidth === window.innerWidth) {
            return;
        }
        lastWidth = window.innerWidth;
        collapseCommentsContentByHeight();
    });



});