jQuery( document ).ready(function( $ ) {

    $(document).on('click', '.assistant-time-content .css-checkbox', function() {
        var type = $(this).data('type');
        $('.guy.'+type).toggleClass('show');
    });

    var w = window.innerWidth;
    $(document).on('click', '.assistant-finances-content .point', function() {
        var type = $(this).data('type');
        $('.name div').removeClass('active').addClass('deactive');
        $('.name .'+type).removeClass('deactive').addClass('active');

        $('.scroller').removeClass().addClass('scroller transition');
        if( w <= 1024 && !$('.assistant-finances-content').length) {
            $(".scroller").addClass(type+'left');
        }
        else {
            $('.scroller').addClass(type);
        }
    });
});

var FlexSlider = function(selector, options) {
    "use strict";

    // Properties
    //==========================================
    var controls,           // next and prev control elements
        container,          // main container
        list,               // list
        items,
        showMore,           // show more left and right block elements
        showMoreCounter,    // show more blocks counter
        direction,          // current move direction
        currentItem,        // current slide element
        currentIndex,       // current slide index
        total,              // total slides
        itemWidth,          // single slide width
        lastWidth,          // last known width of viewport
        defaults
        ;

    defaults = {
       onChange: function(currentItem, currentIndex, direction) {},
       onNextItem: function(currentItem, currentIndex) {},
       onPrevItem: function(currentItem, currentIndex) {}
    };

    init();

    /**
     * Slider constructor
     */
    function init() {
        container = document.querySelector(selector);

        if (!container) {
            //throw new Error("Can't initialize slider with element selector: " + selector);
            console.warn("Can't initialize slider with element selector: " + selector);
            return;
        }

        options = options || {};
        for (var key in defaults) { options.hasOwnProperty(key) && (defaults[key] = options[key]); }
        options = defaults;

        currentIndex = 1;
        showMoreCounter = lastWidth = 0;
        direction = 'next';

        controls = {};
        controls.prev = container.querySelector('.prev');
        controls.next = container.querySelector('.next');

        showMore = {};
        showMore.partial = container.querySelector('.show-more.partial-match');
        showMore.full = container.querySelector('.show-more.full-match');
        list = container.querySelector('.carousel-list');
        items = list.querySelectorAll('li');

        total = items.length;
        total && (currentItem = items[0]);
        recalc();

        var mc  = new Hammer(container);
        mc.on('swiperight', prevSlide);
        mc.on('swipeleft', nextSlide);
        addEvent(controls.prev, 'click', prevSlide);
        addEvent(controls.next, 'click', nextSlide);
        addEvent(showMore.full, 'click', prevSlide);
        addEvent(showMore.partial, 'click', nextSlide);
        addEvent(window, 'resize', recalc);
    }

    /**
     * Move to previous slide
     */
    function prevSlide() {
        direction = 'prev';
        if (currentIndex > 1) {
            move(--currentIndex);
            options.onPrevItem(currentItem, currentIndex);
        }
    }
    /**
     * Move to next slide
     */
    function nextSlide() {
        direction = 'next';
        if (currentIndex < total) {
            move(++currentIndex);
            options.onNextItem(currentItem, currentIndex);
        }

    }
    /**
     * Toggle left and right statistic blocks
     */
    function toggleStatBlocks()
    {
        if (direction === 'next') {
            if (showMoreCounter === 1) {
                showMore.full && showMore.full.classList.remove('is-hidden');
                showMore.partial && showMore.partial.classList.add('is-hidden');
            }
            if (showMoreCounter === 2) {
                showMore.full && showMore.full.classList.add('is-hidden');
            }
            if (!showMoreCounter
                && currentItem.nextElementSibling
                && currentItem.previousElementSibling
                && currentItem.nextElementSibling.classList.contains('secondary')
            ) {
                showMoreCounter++;
                showMore.full && showMore.full.classList.add('is-hidden');
                showMore.partial && showMore.partial.classList.remove('is-hidden');
            } else {
                showMoreCounter && showMoreCounter++;
            }
          } else {
            showMoreCounter && showMoreCounter--;

            if (showMoreCounter === 2) {
                showMore.full && showMore.full.classList.remove('is-hidden');
            }
            if (showMoreCounter === 1) {
                showMore.full && showMore.full.classList.add('is-hidden');
                showMore.partial && showMore.partial.classList.remove('is-hidden');
            }
            if (showMoreCounter === 0) {
                showMore.partial && showMore.partial.classList.add('is-hidden');
            }
        }
    }
    /**
     * Toggle arrow visibility
     *
     * @param pos (next or prev)
     */
    function hideArrow(pos) {
        if (pos === false) {
            controls.prev.style.visibility = 'visible';
            controls.next.style.visibility = 'visible';
        } else {
            controls[pos].style.visibility = 'hidden';
            controls[pos === 'prev' ? 'next' : 'prev'].style.visibility = 'visible';
        }
    }
    /**
     * Go to specified slide
     *
     * @param num
     */
    function move(num) {
        setTranslate3d(list, (1 - (num)) * itemWidth, 0);
        currentItem = items[num-1];

        if (num === total) {
            hideArrow('next');
        } else if (num === 1) {
            hideArrow('prev');
        } else {
            hideArrow(false);
        }
        toggleStatBlocks();
        options.onChange(currentItem, currentIndex, direction);
    }

    /**
     * Recalculate sizes
     */
    function recalc() {
        if (lastWidth === window.innerWidth) {
            return;
        }
        lastWidth = window.innerWidth;
        itemWidth = bmr.getFullElementWidth(items[0]);

        list.style.marginLeft = ((list.offsetWidth - itemWidth) / 2) + 'px';
        var oldT = list.style.transition;
        list.style.transition = 'none';
        move(currentIndex);
        list.style.transition = oldT;
    }

    // Public
    // ...
};

addEvent(window, 'DOMContentLoaded', function()
{
    'use strict';
    CoolSelect('#language-country', {
        filter: true,
        filterPlaceholder: '',
        class: 'country-select'
    });
});
