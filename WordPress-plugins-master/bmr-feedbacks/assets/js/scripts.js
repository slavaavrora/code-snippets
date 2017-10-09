addEvent(window, 'DOMContentLoaded', function()
{
    var $form       = document.getElementById('feedbacks-form'),
        $formText   = document.querySelector('#feedbacks-form [name="comment"]'),
        $formType   = document.querySelector('#feedbacks-form [name="type"]'),
        $formRating = document.querySelector('#feedbacks-form [name="rating"]'),
        $formPostID = document.querySelector('#feedbacks-form [name="postid"]'),
        popupCloseHandler;

    if (!$form || !$formText || !$formType || !$formRating || !$formPostID) {
        return;
    }

    var $ratingNum      = $form.querySelector('#user-rating .num'),
        $ratingSelected = $form.querySelector('#user-rating i'),
        $ratingStars    = $form.querySelector('#user-rating .stars');

    // user rating stars
    addEvent($ratingStars, 'mousemove', function(e)
    {
        var stars = Math.ceil(e.layerX / ($ratingStars.offsetWidth / 5));
        setStars(stars < 1 ? 1 : (stars > 5 ? 5 : stars));
    });

    addEvent($ratingStars, 'mouseleave', function()
    {
        setStars($formRating.value);
    });

    addEvent($ratingStars, 'click', function(e)
    {
        var stars = Math.ceil(e.layerX / ($ratingStars.offsetWidth / 5));
        $formRating.value = stars < 1 ? 1 : (stars > 5 ? 5 : stars);
    });

    // submit form
    addEvent($form, 'submit', function(e)
    {
        e.preventDefault();
        ajax(ajaxurl, {
            data: {
                action : 'feedbacks_new',
                rating : $formRating.value,
                comment: $formText.value,
                postID : $formPostID.value,
                type   : $formType.value
            },
            json  : true,
            method: 'POST',
            success: function(responce)
            {
                if (responce.success) {
                    $formText.value = '';
                    setStars(0);
                    $formRating.value = 0;
                    popupOpen('success', responce.message);
                    typeof window.onFeedbacksNewSuccess === 'function' && (popupCloseHandler = window.onFeedbacksNewSuccess);

                    var $allForm = document.getElementById('all-feedbacks-add-form');
                    $allForm && (popupCloseHandler = function()
                    {
                        $allForm.classList.add('disabled');
                    });
                } else {
                    popupOpen('error', responce.message);
                    typeof window.onFeedbacksNewError === 'function' && (popupCloseHandler && window.onFeedbacksNewError);
                }
            },
            error: function()
            {
                popupOpen('error', 'Error!');
            }
        });
    });

    // likes dislikes
    addEvent('#feedbacks-list [data-id]', 'click', function(e)
    {
        var $self  =  this,
            isLike = this.classList.contains('like');

        e.preventDefault();
        ajax(ajaxurl, {
            data: {
                action     : 'feedbacks_like_dislike',
                feedbackID : $self.dataset.id,
                type       : isLike ? 'like' : 'dislike'
            },
            json  : true,
            method: 'POST',
            success: function(responce)
            {
                if (responce.success) {
                    var $active   = $self.parentNode.querySelector('.active'),
                        $likes    = $self.parentNode.querySelector('.like'),
                        $dislikes = $self.parentNode.querySelector('.dislike');

                    $likes.innerHTML = responce.likes;
                    $dislikes.innerHTML = responce.dislikes;
                    $active && $active.classList.remove('active');
                    $active !== (isLike ? $likes : $dislikes) && (isLike ? $likes : $dislikes).classList.add('active');
                }
            }
        });
    });

    // popup close
    addEvent('#feedbacks-popup .feedbacks-popup-close', 'click', function()
    {
        document.getElementById('feedbacks-popup').classList.remove('active');
        typeof popupCloseHandler === 'function' && popupCloseHandler();
        popupCloseHandler = '';
    });

    function popupOpen(type, message)
    {
        var $popup   = document.getElementById('feedbacks-popup'),
            $content = document.querySelector('#feedbacks-popup .feedbacks-popup-content');

        if ($popup && $content) {
            $popup.classList.add('active');
            $popup.dataset.type = type;
            $content.innerHTML = message;
        }
    }

    function setStars(stars)
    {
        $ratingNum.innerHTML = stars;
        $ratingSelected.style.width = (stars * 20) + '%';
    }

    // all feedbacks form
    addEvent('#all-feedbacks-open-add-form', 'click', function()
    {
        var $form = document.getElementById('all-feedbacks-add-form');
        $form && $form.classList.toggle('disabled');
    });

    // all feedbacks more btn
    addEvent('#all-feedbacks-more-btn', 'click', function()
    {
        if (this.classList.contains('active')) {
            return;
        }

        var $self      = this,
            curPage    = parseInt(this.dataset.currentPage),
            $container = document.getElementById('all-feedbacks-list');

        $container && ajax(ajaxurl, {
            data: {
                action: 'feedbacks_items_page',
                page  : curPage + 1,
                postID: $self.dataset.postid
            },
            method: 'POST',
            beforeSend: function()
            {
                $self.classList.add('active');
            },
            success: function(data)
            {
                if (data) {
                    $container.innerHTML += data;
                    curPage++;
                }
            },
            complete: function()
            {
                $self.classList.remove('active');
                $self.dataset.currentPage = curPage;
                curPage == $self.dataset.totalPages && $self.classList.add('disabled');
            }
        });
    });
});