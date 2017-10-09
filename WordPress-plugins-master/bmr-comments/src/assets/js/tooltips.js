addEvent(window, 'DOMContentLoaded', function()
{
    "use strict";

    /**
     * TOOLTIPS
     * =================================================================
     */
    var tt = document.createElement('div');
    var tta = document.createElement('div');
    tt.classList.add('bmr-tt-body', 'comments-tt');
    tta.classList.add('bmr-tt-arrow', 'comments-tt');
    document.body.appendChild(tt);
    document.body.appendChild(tta);

    var show = function(content, extraClass){
        var spaceTop = 20,
            el = this,
            elOffsets = bmr.getPosition(el);

        tt.removeAttribute('style');
        tta.removeAttribute('style');
        tt.innerHTML = '';

        if (extraClass) {
            tt.classList.add(extraClass);
        } else {
            tt.className = 'bmr-tt-body comments-tt';
        }

        if (!(tt.innerHTML = content)) {
            return;
        }

        var posLeft = elOffsets.x + el.offsetWidth/2 - tt.offsetWidth/2;
        var overRightBounce = document.body.offsetWidth - posLeft - tt.offsetWidth - 5;
        var overLeftBounce = posLeft - 5;

        overRightBounce < 0 && (posLeft+=overRightBounce);
        overLeftBounce < 0 && (posLeft-=overLeftBounce);

        tt.style.top = elOffsets.y - tt.offsetHeight - spaceTop + 'px';
        tt.style.left = posLeft + 'px';
        tta.style.top = elOffsets.y - tta.offsetHeight - spaceTop + 7 + 'px';
        tta.style.left = elOffsets.x + el.offsetWidth/2 - tta.offsetWidth/2 + 'px';

        tt.style.opacity = 1;
        tta.style.opacity = 1;
    };

    var hide = function() {
        tt.style.opacity = 0;
        tta.style.opacity = 0;
    };

    var $commentListContainer = document.getElementById('comments-list'), tooltip;

    if (!$commentListContainer) {
        return;
    }

    $commentListContainer.addListener('touchstart mouseover', '[data-tooltip]', function() {
        show.call(this, this.dataset['tooltip']);
    });
    $commentListContainer.addListener('touchend mouseout', '[data-tooltip]', hide);

    $commentListContainer.addListener('mouseover touchstart', '.interlocutors .to', function()
    {
        var $comment, $parentComment, avatar, content, user;
        $comment = this.parents('.bmr-comment');
        $parentComment = document.getElementById('comment-' + $comment.dataset['parent']);

        if (!$parentComment) {
            return;
        }

        content = $parentComment.querySelector('.comment-content').innerHTML;
        content = bmr.htmlTruncate(content, 150).trim();
        avatar = $parentComment.querySelector('.comment-avatar').outerHTML;
        user = $parentComment.querySelector('.interlocutors .from').childNodes[0].textContent.trim();

        var html = [
            '<div class="comment-preview bmr-comment">',
                avatar,
                '<div class="comment-body">',
                    '<div class="comment-head"><span class="from">' + user + '</span></div>',
                    '<div class="comment-content">' + content + '</div>',
                '</div>',
            '</div>'
        ].join('\n');

        show.call(this, html, 'comments-tt-preview');
    });

    $commentListContainer.addListener('mouseout touchend', '.interlocutors .to', hide);

    var socialsHtml = $commentListContainer.querySelector('.comment-socials');

    if (!socialsHtml) {
        return;
    }
    socialsHtml = socialsHtml.innerHTML;
    $commentListContainer.addListener('click', '.comment-share', function(e) {
        e.preventDefault();
        if (window.innerWidth > 500) {
            return;
        }
        show.call(this, socialsHtml, 'comments-tt-socials');
    });


});