var Quiz = {};

Quiz.Footer = function()
{
    'use strict';

    // Properties
    //==========================================
    var $overlay            // overlay
        , vw                // last viewport width
        , $container        // footer container
        , $editBtn          // edit button
        , $lastItemsPanel   // last items panel
        , $lastItemsCont    // last items container
        , $itemsCont        // selected items container
        , isOpen            // current footer state
        , _this             // this

        // Animations props
        , overlaySpeed      // overlay transition duration
        , footerSpeed       // footer transition duration

        // Criteria props
        , criteria
        , $counter
        , elemsTop
        ;

    // Methods
    //==========================================
    function init()
    {
        initVars();

        displayToggle($editBtn);

        setTransitionDuration($container, footerSpeed);
        setTransitionDuration($itemsCont, footerSpeed);
        setTransitionDuration($overlay,   overlaySpeed);

        addEvent(window, 'resize', onResize);
        addEvent($editBtn, 'click', onEditBtnClick);
        addEvent($overlay, 'click', close);

        $container.addListener('click', '#more-items-counter', onEditBtnClick);
        $itemsCont.addListener('click', '.delete-btn', function() {
            criteria.remove(this.parents('.item').dataset['id']);
        });

        onResize();
    }

    function initVars()
    {
        _this = this;
        isOpen = false;
        vw = window.innerWidth;
        $overlay = addOverlay();
        $container = document.querySelector('.assistant-footer');
        $editBtn = $container.querySelector('.edit-btn');
        $lastItemsPanel = document.querySelector('.panel-items');
        $lastItemsCont = document.getElementById('last-items');
        $itemsCont = document.getElementById('assistant-criteria');
        $counter = document.getElementById('more-items-counter');
        elemsTop = 3;

        overlaySpeed = 300;
        footerSpeed  = 300;
    }

    function setTransitionDuration(elem, duration)
    {
        elem.style.transitionDuration = duration + 'ms';
        elem.style.webkitTransitionDuration = duration + 'ms';
    }

    function onEditBtnClick(e)
    {
        e.preventDefault();
        !isOpen ? open() : close();
    }

    function open()
    {
        if (isOpen) {
            return;
        }
        // Hide last items
        displayToggle($lastItemsPanel);
        moveLastItems('bottom');

        swapText($editBtn);

        // Calc items container height
        $itemsCont.classList.add('disable-transition');
        {
            $itemsCont.style.height = 'auto';
            var height = $itemsCont.offsetHeight;
            $itemsCont.style.height = 0;
        }
        $itemsCont.classList.remove('disable-transition');

        // Show overlay
        toggleOverlay();

        // Set calculated height
        setTimeout(function() {
            $container.style.marginTop = -(height) + 'px';
            $itemsCont.style.height = height + 'px';
            focus();
        }, 1);
        $container.classList.add('is-open');
        isOpen = true;
    }

    function close()
    {
        if (!isOpen) {
            return;
        }
        swapText($editBtn);
        $itemsCont.style.height = 0;
        $container.style.marginTop = 0;
        toggleOverlay();

        setTimeout(function() {
            document.body.style.top = '';
            moveLastItems('top');
            displayToggle($lastItemsPanel);

            $container.classList.remove('is-open');
            isOpen = false;
            for (var i = criteria.queue.length; i--; criteria.add(criteria.queue[i].id, criteria.queue[i].criteria)) {}

        }, footerSpeed);
    }

    function moveLastItems(whereTo)
    {
        whereTo = whereTo || 'bottom';
        var items, i;

        if (whereTo === 'bottom') {
            items = $lastItemsCont.querySelectorAll('.item');
            for (i = items.length; i--;) {
                $itemsCont.insertBefore(items[i], $itemsCont.firstChild);
            }
        } else {
            items = $itemsCont.querySelectorAll('.item:nth-child(-n+' + elemsTop + ')');
            for (i = 0; i < items.length; i++) {
                $lastItemsCont.appendChild(items[i]);
            }
        }
    }

    /**
     * Scrolls to footer top if necessary
     */
    function focus()
    {
        setTimeout(function() {
            var adminbar = document.getElementById('wpadminbar'),
                adminbarH = adminbar ? adminbar.offsetHeight : 0,
                t = $container.getBoundingClientRect().top;

            if (t < adminbarH) {
                t = -(t - adminbarH);
                var end = document.body.scrollTop - t - 50,
                    step = t / (300 / 30);

                var timer = setInterval(function() {
                    if (document.body.scrollTop <= end) {
                        clearInterval(timer);
                        return;
                    }
                    document.body.scrollTop -= step;
                }, 30);
            }
        }, footerSpeed + 100);
    }

    function swapText(elem)
    {
        var text = elem.dataset['swapText'];
        elem.dataset['swapText'] = elem.textContent;
        elem.textContent = text;
    }

    function displayToggle(elem)
    {
        elem.classList.toggle('is-hidden');
    }

    function addOverlay()
    {
        if (document.getElementById('assistant-overlay')) {
            return;
        }
        var overlay = document.createElement('div');
        overlay.id = "assistant-overlay";
        document.body.appendChild(overlay);
        return overlay;
    }

    function toggleOverlay()
    {
        if ($overlay.style.opacity == 0) {
            $overlay.style.display = 'block';
            setTimeout(function() {
                $overlay.style.opacity = .75;
            }, 1);
        } else {
            $overlay.style.opacity = 0;
            setTimeout(function() {
                $overlay.style.display = 'none';
            }, footerSpeed);
        }
    }

    function onResize()
    {
        // > 1024, 500 - 950  = 3
        // < 500, 950 - 1024 = 2
        if ((window.innerWidth > 500 && window.innerWidth <= 950) || window.innerWidth > 1024) {
            elemsTop = 3;
        } else if (window.innerWidth < 414) {
            elemsTop = 1;
        } else {
            elemsTop = 2;
        }

        if (window.innerWidth === vw) {
            return;
        }
        vw = window.innerWidth;
    }

    criteria = {
        data: [],
        unreset: [],
        cnt: 0,
        templates: {
            img:  '<img src="{{src}}">',
            icon: '<div class="icon"><i class="{{src}}"></i></div>',
            text: '<div class="icon">{{src}}</div>'
        },
        queue: [],
        clearCnt: 0,

        add: function(id, criteria) {
            if (isOpen) {
                this.queue.push({id: id, criteria: criteria});
                return;
            }
            if (typeof this.data[id] === 'undefined') {
                var item = document.createElement("div"), html;
                item.className = 'item is-appearing';
                item.dataset.id = id;

                html = [
                    '<div class="item-wrap">',
                    '<div class="delete-btn"><i class="icon-close"></i></div>',
                    this.templates[criteria.type].replace('{{src}}', criteria.icon),
                    '<div class="text">' + criteria.text + '</div>',
                    '</div>'
                ].join('');

                item.innerHTML = html;
                if ($lastItemsCont.children.length >= elemsTop) {
                    this.cnt++;
                    $counter.dataset['num'] = this.cnt;
                    var last = $lastItemsCont.querySelector('.item:last-child');
                    $itemsCont.insertBefore(last, $itemsCont.firstChild);
                }
                $lastItemsCont.insertBefore(item, $lastItemsCont.firstChild);

                setTimeout(function() {
                    item.classList.remove('is-appearing');
                }, 300);

                if ($editBtn.classList.contains('is-hidden')) {
                    displayToggle($editBtn);
                }

                this.data[id] = criteria;
            } else if (JSON.stringify(this.data[id]) !== JSON.stringify(criteria)) {
                this.remove(id);
                this.add(id, criteria);
            }
            //this.serialize();
        },

        get: function(id, fieldKey) {
            return typeof this.data[id] !== 'undefined' ? this.data[id][fieldKey] : false;
        },

        resetElem: function(id) {
            var elem = id.match('criteria')
                     ? document.querySelector('[name="' + id + '"]:checked')
                     : document.getElementById(id);

            if (!elem) {
                criteria.unreset.push(id);
                return;
            }

            if (elem.type === 'checkbox' || elem.type === 'radio') {
                elem.checked = false;
            } else if (elem.type === 'hidden') {
                var html = elem.outerHTML, match;
                var re = /data-default-(\b.+?\b)=['"](.+?)['"]/g;

                while (match = re.exec(html)) {
                    if (match[1] === 'value') {
                        elem.value = match[2];
                    } else {
                        elem.dataset[match[1]] = match[2];
                    }
                }
            } else if (elem.type === 'select-one') {
                elem.value = '0';
            }

            var event = document.createEvent('Event');
            event.initEvent('change', true, false);
            elem.dispatchEvent(event);
        },

        remove: function(id) {
            if (typeof criteria.data[id] === 'undefined') {
                return;
            }

            delete criteria.data[id];
            //criteria.serialize();
            var item = document.querySelector('[data-id="' + id + '"]');
            item.classList.add('is-disappearing');

            setTimeout(function() {
                item && item.parentNode.removeChild(item);
                criteria.cnt > 0 && criteria.cnt--;
                $counter.dataset['num'] = criteria.cnt;

                if (!isOpen && $lastItemsCont.children.length < elemsTop && $itemsCont.children.length) {
                    $lastItemsCont.appendChild($itemsCont.firstChild);
                }

                console.info(criteria.clearCnt);
                (isOpen || criteria.clearCnt) && criteria.resetElem(id);

                if ($itemsCont.children.length === 0) {
                    $lastItemsCont.children.length === 0 && displayToggle($editBtn);
                    //criteria.clearCnt = 0;
                    close();
                }
            }, 300);
        },

        //clear: function() {
        //    criteria.clearCnt = criteria.data.length;
        //    console.log(criteria.clearCnt);
        //    for (var key in criteria.data) {
        //        criteria.data.hasOwnProperty(key) && criteria.remove(key);
        //    }
        //},

        serialize: function() {

            if (!isLocalStorageSupported()) {
                return;
            }

            var str = [];
            for (var key in this.data) {
                this.data.hasOwnProperty(key) && str.push(key + '||' + JSON.stringify(this.data[key]));
            }
            str = str.join('#');
            localStorage["bmr-quiz-data"] = str;
        },

        unserialize: function(data) {
            if (!data || typeof data !== 'string') {
                return;
            }
            var arr = data.split('#'), item, id, tmp;
            arr.forEach(function(value) {
                tmp = value.split('||');
                id = tmp[0];
                item = JSON.parse(tmp[1]);
                criteria.add(id, item);
            });
        }
    };

    // set public
    this.init = init;
    this.criteria = criteria;
    this.open = open;
    this.close = close;

    window.clearCriteria = criteria.clear;
};
