(function() {
    'use strict';

    if (location.hash) {
        setTimeout(function() {
            window.scrollTo(0, 0);
        }, 1);
    }

    /** @var Object quiz */

    addEvent(window, 'DOMContentLoaded', function()
    {
        var $slider = document.getElementById('avg-slider');

        noUiSlider.create($slider, {
            animate: true,
            snap: false,
            start: 1000,
            step: 1,
            range: {
                'min': 1,
                '35%': 1000,
                'max': 50000
            }
        });

        (function()
        {
            var handles  = $slider.querySelectorAll('.noUi-handle'),
                tooltips = [],
                leftPos = 0;

            for (var i = 0; i < handles.length; i++) {
                tooltips[i] = document.createElement('div');
                tooltips[i].classList.add('handle-tip');
                handles[i].appendChild(tooltips[i]);
            }

            $slider.noUiSlider.on('update', function(values) {
                tooltips[0].textContent =  parseInt(values[0]);
                leftPos = parseFloat(handles[0].parentNode.style.left);
            });
        })();

        var $content = document.getElementById('assistant-content'),
            isHistorySupported = (window.history && window.history.pushState),
            Footer;

        if (!$content) {
           return;
        }

        Footer = new Quiz.Footer();
        Footer.init();

        // > PARTIALS LOADER
        //**********************************************
        (function(){
            var $quizControlsCont = document.getElementById('assistant-controls'),
                $quizBreadcrumbsCont = document.getElementById('assistant-breadcrumbs'),
                $contentPlaceholder = $content.querySelector('#js-ajax-content-placeholder'),
                $spinner = $content.querySelector('.bmr-loading-spinner'),
                $nextBtn = $quizControlsCont.querySelector('.next'),
                $prevBtn = $quizControlsCont.querySelector('.prev'),
                $titleLeft  = $prevBtn.querySelector('.arrow-title'),
                $titleMain  = $quizControlsCont.querySelector('.quiz-heading'),
                $titleRight = $nextBtn.querySelector('.arrow-title'),
                $quizCrumbs = $quizBreadcrumbsCont.querySelectorAll('li'),
                $form = document.getElementById('quiz-form'),
                $breadcrumb = document.querySelector('.breadcrumbs li:last-of-type span'),
                $nextBtnMobile = $content.querySelectorAll('.next-btn'),
                mainData = {action: 'query'},
                $assistantContainer = document.querySelector('.assistant');

            var Matches = {
                _partially: document.getElementById('matched-partially'),
                _fully: document.getElementById('matched-fully'),
                found:  0,

                init: function() {
                    this._nums = {
                        fully:  this._fully.querySelectorAll('.n'),
                        partially:  this._partially.querySelectorAll('.n')
                    };
                    this._text = {
                        fully: this._fully.querySelector('.matches-text'),
                        partially: this._partially.querySelector('.matches-text')
                    };
                },

                set: function(num, type) {
                    typeof num !== 'string' && (num = num.toString());
                    this.found = num;

                    for (var i = 0; i < this._nums[type].length; i++) {
                        this._nums[type][i].style.display = num[i] ? 'inline-block' : 'none';
                        this._nums[type][i].dataset['num'] = num[i] ? num[i] : 0;
                    }
                    this._text[type].textContent = window.pluralForm(num, quiz.i18n.matches[type]);

                    if (num == 0 && type === 'partially') {
                        this._partially.classList.add('is-hidden');
                    } else {
                        this._partially.classList.remove('is-hidden');
                    }
                },

                get: function() {
                    return this.found;
                }
            };
            Matches.init();

            /**
             * TOOLTIPS
             * =================================================================
             */
            function showTooltip(elem, text) {
                if (!elem) {
                    return;
                }
                text = text || '';
                var $tooltip = document.getElementById('assistant-tooltip');
                $tooltip && $tooltip.parentNode.removeChild($tooltip);

                var div = document.createElement('div');
                div.className = 'assistant-tooltip';
                div.id = 'assistant-tooltip';
                div.textContent = text;  //this.dataset['tooltip']

                var rect = elem.getBoundingClientRect();
                var leftPos = rect.left + (rect.width / 2);
                var right = window.innerWidth - rect.left;

                document.body.appendChild(div);

                var elemRect = div.getBoundingClientRect();
                var pos = getPosition(elem);

                //console.info('top: %d, left: %d, width: %d, right: %d, elemWidth: %d', rect.top, rect.left, rect.width, right, elemRect.width, rect);
                if (elemRect.height < rect.top && (elemRect.width/2) < (rect.left+rect.width / 2 + 10) && (elemRect.width / 2) < (right - rect.width/2)) {
                    div.classList.add('top');
                    div.style.left = (leftPos - (elemRect.width / 2) - 5) + 'px';
                    div.style.top = (pos.y - elemRect.height - 15) + 'px';

                } else if (elemRect.width >= right) {
                    div.classList.add('right');
                    div.style.left = (rect.left - elemRect.width - 10) + 'px';
                    div.style.top = (pos.y + (rect.height / 2) - (elemRect.height / 2) - 5) + 'px';

                } else if (elemRect.width >= rect.left) {
                    div.classList.add('left');
                    div.style.left = rect.right + 10 + 'px';
                    div.style.top = (pos.y + (rect.height / 2) - (elemRect.height / 2) - 5) + 'px';
                }

                setTimeout(function() {
                    div.parentNode && div.parentNode.removeChild(div);
                }, 1500);
            }

            function getPosition(element, absolute) {
                absolute = typeof absolute === 'undefined' ? true : absolute;
                var xPosition = 0;
                var yPosition = 0;

                while(element) {
                    xPosition += (element.offsetLeft - (absolute ? 0 : element.scrollLeft) + element.clientLeft);
                    yPosition += (element.offsetTop - (absolute ? 0 : element.scrollTop) + element.clientTop);
                    element = element.offsetParent;
                }
                return {x: xPosition, y: yPosition};
            }
            /* ==================================================================== */

            function swapText(elem)
            {
                var text = elem.dataset['swapText'];
                elem.dataset['swapText'] = elem.textContent;
                elem.textContent = text;
            }

            var pressedElem = null, block = !!location.search.match('block');

            // Quiz menu item click
            addEvent($quizCrumbs, 'click', function(e) {
                isHistorySupported && e.preventDefault();
                pressedElem = this;
                Navigation.go(this.dataset['partialSlug']);
            });
            // Next quiz step click
            addEvent($nextBtn, 'click' ,function() {
                pressedElem = this;
                Navigation.goNext();
            });
            // Prev quiz step click
            addEvent($prevBtn, 'click' ,function() {
                Navigation.goPrev();
            });
            // Quiz start button click
            addEvent('#start-quiz', 'click', function() {
               Navigation.go();
            });
            // Results button click
            document.body.addListener('click', '.result-btn, .again-btn', function(e) {
                isHistorySupported && e.preventDefault();

                if (location.hash === '#results') {
                    //history.back();
                    Navigation.go();
                } else {
                    Navigation.go(this.hash);
                }
            });
            // Next quiz step click (mobile)
            $content.addListener('click', '.next-btn', function() {
                pressedElem = this;
            });

            // History change handler
            isHistorySupported && window.addEventListener('popstate', function(e) {
                var slug = e.state && e.state.slug ? e.state.slug : location.hash.replace('#', '');
                var num = Navigation.map.indexOf(slug);
                num !== -1 && Navigation.go(num);
            });

            var Navigation = {
                current: 0,
                currentSlug: '',
                currentMenuItem: null,
                map: [],
                max: $quizCrumbs.length,
                cache: {},
                isLoading: false,

                init: function() {
                    this.buildNavMap();
                    //this.go(location.hash.length ? location.hash : null);
                },

                /**
                 * Build partial map based on navigation menu
                 */
                buildNavMap: function() {
                    if ($quizCrumbs.length == 0) {
                        return;
                    }
                    for (var i = 0; i < $quizCrumbs.length; i++) {
                        this.map[i+1] = $quizCrumbs[i].dataset['partialSlug'];
                    }
                },

                isNextAllowed : function(num) {
                    return num === 1 || !!$quizBreadcrumbsCont.querySelector('li.is-filled:nth-of-type(1)');
                },

                parseNum: function(num) {
                    if (typeof num === 'string' && isNaN(num)) {
                        num = num.replace('#', '');
                        num = this.map.indexOf(num);
                        num = num !== -1 ? num : this.current;
                    } else {
                        num = parseInt(num);
                    }
                    return num;
                },

                /**
                 * Load specified partial by num or hash
                 * @param num
                 */
                go: function(num) {
                    var self = this;
                    num = num || 1;
                    num = this.parseNum(num);

                    if (this.isLoading || num == this.current || num < 0 || num > this.max) {
                        return;
                    }

                    if (num !== 9 && num > this.current && !this.isNextAllowed(num)) {
                        showTooltip(pressedElem, quiz.i18n.tooltip);
                        this.go(1);
                        return;
                    }

                    self.current !== 9 && self.current && document.getElementById(self.map[self.current]).classList.add('is-hidden');
                    if (num === 9) {
                        Footer.close();
                        //!mainData.results && swapText($resultBtn);
                        mainData.results = 1;
                        loadQuizStep(num, function(status) {

                            if (!status) {
                                return;
                            }
                            self.current = num;
                            num === 9 && sendData();

                            // Dirty IE Hack
                            setTimeout(function() {
                                self.afterPartialLoaded();
                            }, 100);
                        });

                    } else {
                        //$quizControlsCont.classList.remove('is-hidden');
                        $contentPlaceholder.innerHTML = '';
                        document.getElementById(self.map[num]).classList.remove('is-hidden');
                        self.current = num;
                        self.afterPartialLoaded();

                        //mainData.results === 1 && swapText($resultBtn);
                        mainData.results && delete mainData.results;
                    }
                },
                /**
                 * Load next partial
                 */
                goNext: function() {
                    var next = this.current + 1;
                    next = next > this.max ? this.max : next;
                    this.go(next);
                },
                /**
                 * Load previous partial
                 */
                goPrev: function() {
                    var prev = this.current - 1;
                    prev = prev == 0 ? this.current : prev;
                    this.go(prev);
                },

                afterPartialLoaded: function() {
                    this.setActive(this.current);
                    this.setHeading();
                    this.setBreadcrumb();

                    if (Footer.criteria.unreset.length) {
                        for (var i = Footer.criteria.unreset.length; i--;) {
                            Footer.criteria.resetElem(Footer.criteria.unreset[i]);
                            delete Footer.criteria.unreset[i];
                        }
                    }

                    $assistantContainer.dataset['currentPage'] = this.currentSlug;
                    $nextBtn.classList[this.currentSlug === 'results' ? 'add' : 'remove']('is-hidden');
                    $prevBtn.classList[this.currentSlug === 'results' ? 'add' : 'remove']('is-hidden');
                    $quizBreadcrumbsCont.classList[this.currentSlug === 'results' ? 'add' : 'remove']('is-hidden');

                    if (this.currentSlug === 'results') {
                        new FlexSlider('.carousel', {
                            onChange: function(currentItem) {
                                var criteria = currentItem.querySelector('.item').dataset.criteria,
                                    matches = setMatches(criteria),
                                    bkTitle = currentItem.querySelector('.item > .title').textContent,
                                    resultsTitle = document.getElementById("results-title"),
                                    resultsBk,
                                    resultsMatched;

                                resultsBk = resultsTitle.querySelector('.bookmaker');
                                resultsMatched = resultsTitle.querySelector('.num-matched');
                                bkTitle = bkTitle.replace(/\d+\.\s/, '');

                                resultsBk.textContent = bkTitle;
                                resultsMatched.textContent = matches;
                            }
                        });
                    }

                    if (!isHistorySupported) {
                        location.hash = '#' + this.currentSlug;
                    } else {
                        if (history.state && history.state.slug === this.currentSlug) {
                            history.replaceState({slug: this.currentSlug}, null, '#' + this.currentSlug);
                        } else {
                            setTimeout(function() {
                                history.pushState({slug: Navigation.currentSlug}, null, '#' + Navigation.currentSlug);
                            }, 100)
                        }
                    }

                    // Set defaults on special pages
                    setTimeout(function() {
                        if (Navigation.current == 3) {
                            var $playerType = document.getElementById('player-type');
                            $playerType && $playerType.triggerEvent('change');
                        }
                        if (Navigation.current == 5) {
                            var $avg = document.getElementById('finances-avg'),
                                $highs = document.getElementById('finances-highs'),
                                $currency = document.querySelector('[name="finances[currency]"]:checked');

                            $avg && $avg.triggerEvent('change');
                            $highs && $highs.triggerEvent('change');
                            $currency && $currency.triggerEvent('change');
                        }
                    }, 100);
                },

                setFilledState: function(slug) {
                    var $item =  $quizBreadcrumbsCont.querySelector('[data-partial-slug="' + slug + '"]');
                    $item && $item.classList[mainData[slug] ? 'add' : 'remove']('is-filled');
                },

                setBreadcrumb: function() {
                    if ($breadcrumb) {
                        $breadcrumb.textContent = this.currentMenuItem.textContent.replace(/(\s|\d)+/, '');
                        $breadcrumb.parentNode.classList.remove('hide');
                    }
                },

                setActive: function(num) {

                    for (var i = 0; i < $quizCrumbs.length; i++) {
                        var action = i+1 == num ? 'add' : 'remove';
                        $quizCrumbs[i].classList[action]('active');
                        action === 'add' && (this.currentMenuItem = $quizCrumbs[i]);
                    }

                    for (i = $nextBtnMobile.length; i--;) {
                        $nextBtnMobile[i].classList.add('is-hidden');
                    }
                    $nextBtnMobile[num] && $nextBtnMobile[num].classList.remove('is-hidden');

                    if (num <= 1) {
                        $prevBtn.classList.add('is-disabled')

                    } else if (num == this.max) {
                        $nextBtn.classList.add('is-disabled')

                    } else {
                        $prevBtn.classList.remove('is-disabled');
                        $nextBtn.classList.remove('is-disabled');
                    }
                },
                setHeading: function() {
                    var num = this.current !== 0 ? this.current : 1;

                    var $menuItem = $quizBreadcrumbsCont.querySelector('li:nth-of-type('+ num + ')');
                    $titleMain.textContent = $menuItem.dataset['partialHeading'];
                    this.currentSlug = $menuItem.dataset['partialSlug'];

                    var titleL = $menuItem.previousElementSibling
                               ? $menuItem.previousElementSibling.querySelector('.menu-title').textContent
                               : '';
                    var titleR = $menuItem.nextElementSibling
                               ? $menuItem.nextElementSibling.querySelector('.menu-title').textContent
                               : '';

                    $titleLeft.textContent = titleL;
                    $titleRight.textContent = titleR;
                }
            };
            Navigation.init();

            /**
             * Load partial
             * @param id
             * @param callback
             */
            function loadQuizStep(id, callback) {
                ajax(quiz.ajaxurl,{
                    data: {
                        action: 'get_assistant_partial',
                        id: id,
                        locale: quiz.i18n.locale
                    },
                    beforeSend: function() {
                        $spinner.style.display = 'block';
                        setLoadingTitle();
                    },
                    success: function(response) {
                        if (response.success) {
                            $contentPlaceholder.innerHTML = response.content;
                        } else {
                            console.warn(response.errors);
                        }
                        setLoadingTitle(' ');
                        callback(response.success);
                    },
                    error: function(xhr) {
                        console.warn('Error: %s\n', status, xhr);
                    },
                    complete: function() {
                        $spinner.style.display = 'none';
                    },
                    json: true
                });
            }

            //=================================================================
            // PRODUCT
            (function(){
                var $product = document.getElementById('products');
                $form.addListener('change', '#products .css-checkbox', function() {
                    var $label = this.parentNode.querySelector('label');
                    if (this.checked) {
                        Footer.criteria.add(this.id, {
                            text: $label.innerHTML,
                            type: 'icon',
                            icon: $label.dataset.icon
                        });
                    } else {
                        Footer.criteria.remove(this.id);
                    }
                    setData(this.name, getCheckedValues($product));
                    Navigation.setFilledState('products');
                });
            })();

            // DEVICES
            (function(){
                var $devices = document.getElementById('devices'), triggering = false, lastItem;

                $form.addListener('change', '#devices .css-checkbox', function() {
                    // Toggle tablet, phone device types
                    var $hiddenCheckboxes, $item, $topLevel;
                    $item = this.parents('.item');
                    $topLevel = $item.querySelector('.top-level');
                    $hiddenCheckboxes = $item.querySelector('.hidden-checkbox');

                    // Toggle hidden checkboxes under top level one
                    this === $topLevel && $hiddenCheckboxes && $hiddenCheckboxes.classList.toggle('is-shown');

                    // Check top level if one of childs is checked
                    if ($hiddenCheckboxes && !$hiddenCheckboxes.classList.contains('is-shown') && this !== $topLevel && this.checked) {
                        $topLevel.checked = true;
                        $topLevel.triggerEvent('change');
                    }

                    // Show icon in device
                    var type = this.dataset['type'];
                    type && bmr.toggle($item.querySelector('.' + type + '-item'));

                    // Uncheck top level checkbox is there are no checked childs
                    if (!$item.querySelectorAll('.hidden-checkbox :checked').length && $topLevel !== this) {
                        $topLevel.checked = false;
                        $item.querySelector('.hidden-checkbox').classList.remove('is-shown');
                        Footer.criteria.remove($topLevel.id);
                    }

                    // Check another type if android or wphone is checked
                    if (!triggering && type && (type == 'android' || type == 'wphone')) {
                        var itemToCheck = $item.parentNode.querySelector('input[data-type="' + type +'"]:not([value="'+ this.value +'"])');
                        if (itemToCheck && itemToCheck.checked !== this.checked) {
                            itemToCheck.checked = this.checked;
                            triggering = true;
                            itemToCheck.triggerEvent('change');

                            setTimeout(function () {
                                triggering = false;
                            }, 200);
                        }
                    }

                    if (this === $topLevel) {
                        var $label = this.parentNode.querySelector('label'),
                            id = $label.getAttribute('for');

                        if (this.checked) {
                            Footer.criteria.add(id, {
                                text: $label.innerHTML,
                                type: 'icon',
                                icon: $label.dataset.icon
                            });
                        } else {
                            // Create native change event
                            var event = document.createEvent('Event'), checked;
                            event.initEvent('change', true, false);
                            checked = $hiddenCheckboxes ? $hiddenCheckboxes.querySelectorAll(':checked') : [];

                            for (var i = checked.length; i--;) {
                                checked[i].checked = false;
                                checked[i].dispatchEvent(event);
                            }
                            Footer.criteria.remove(id);
                        }
                    }
                    setData(this.name, getCheckedValues($devices));

                    if ($hiddenCheckboxes) {
                        var items = $hiddenCheckboxes.querySelectorAll('input:checked');

                        if (items.length < 2) {
                            return;
                        }
                        for (var i = items.length; i--;) {
                            if (this !== items[i]) {
                                items[i].checked = false;
                                items[i].triggerEvent('change');
                            }
                        }
                    }
                });
            })();

            // PLAYER
            (function(){
                var $player, $playerTypeOther, $playerType;
                $player = document.getElementById('player');

                $form.addListener('click', '#player .point', function()
                {
                    $playerType = $playerType || document.getElementById('player-type');

                    var oldVal = $playerType.value;
                    if (oldVal !== this.dataset['type']) {
                        $playerType.value = this.dataset['type'];
                        $playerType.dataset['label'] = this.textContent;
                        $playerType.triggerEvent('change');
                    }
                });

                $form.addListener('change', '#player input', function()
                {
                    $playerTypeOther = $playerTypeOther || document.getElementById('player-type-other');
                    if (this.type === 'hidden') {

                        var $radio, text = this.dataset['label'];
                        this.parentNode.parentNode.dataset['position'] = this.value;

                        if (this.value !== 'expert') {
                            $radio = $player.querySelector('.css-radio:checked');
                            $radio && ($radio.checked = false);
                            $playerTypeOther.value = '';
                            setData('player[extra]', '');
                        }
                        Footer.criteria.add(this.id, {
                            text: text,
                            type: 'icon',
                            icon: 'icon-player-' + this.value
                        });
                    } else {
                        $playerTypeOther && ($playerTypeOther.disabled = this.value !== 'other');
                    }
                    setData(this.name, this.value === 'other' ? $playerTypeOther.value : this.value);
                });
            })();

            // TIME
            (function() {
                var $time = document.getElementById('time');
                $time.addListener('click', 'input', function() {
                    setData(this.name, getCheckedValues($time));
                });
            })();

            // FINCANCES
            (function() {
                var $avg, $highs, $finances, highsChanged = false, $currCode, lastCurrencyId = false;

                $finances = document.getElementById('finances');
                $highs = document.getElementById('finances-highs');
                $avg = document.getElementById('finances-avg');
                $currCode = $finances.querySelectorAll('.currency-code');

                $form.addListener('click', '#finances .point', function() {
                    if (highsChanged) {
                        setTimeout(function() {
                            highsChanged = false;
                        }, 100);
                        return;
                    }
                    $highs = $highs || document.getElementById('finances-highs');
                    $highs.value = this.dataset['type'];
                    $highs.triggerEvent('change');
                });

                $slider.noUiSlider.on('change', function(value) {
                    $avg.value = parseInt(value);
                    $avg.triggerEvent('change');
                });

                $form.addListener('change', '#finances input', function() {
                    if (this.type === 'hidden') {
                        if (this === $highs) {
                            highsChanged = true;
                            $finances.querySelector('[data-type="' + this.value + '"').triggerEvent('click');
                            Footer.criteria.add(this.id, {
                                text: this.value === 'yes' ? quiz.i18n['highs_cut_yes'] : quiz.i18n['highs_cut_no'],
                                type: 'icon',
                                icon: this.value === 'yes' ? 'icon-sad' : 'icon-smiley'
                            });
                        } else if (this === $avg) {
                            var text = this.value + ' ' + $currCode[0].textContent;
                            $slider.noUiSlider.set(this.value);
                            Footer.criteria.add(this.id, {
                                text: text,
                                type: 'icon',
                                icon: 'icon-icon4'
                            });
                        }
                        setData(this.name, this.value);
                    } else {
                        if (this.checked) {
                            var $label = this.parentNode.querySelector('label'), text;
                            text = $label.textContent;

                            for (var i = $currCode.length; i--;) {
                                $currCode[i].textContent = this.dataset['termName'];
                            }

                            if (lastCurrencyId) {
                                Footer.criteria.remove(lastCurrencyId);
                            }

                            lastCurrencyId = this.id;
                            Footer.criteria.add(this.id, {
                                text: text == this.dataset['termName'] ? '' : text,
                                type: 'text',
                                icon: this.dataset['termName']
                            });
                            $avg.triggerEvent('change');
                        }
                        setData(this.name, getCheckedValues($finances));
                    }
                });
            })();

            // PAYMENT
            (function() {
                var $payment = document.getElementById('payment');
                $form.addListener('change', '#payment .css-checkbox', function() {

                    var $label = this.parentNode.querySelector('label');
                    var $img = this.parentNode.querySelector('img');

                    if (this.checked) {
                        Footer.criteria.add(this.id, {
                            text: $label.textContent,
                            type: ($img ? 'img' : 'text'),
                            icon: ($img ? $img.src : 'No Image')
                        });
                    } else {
                        Footer.criteria.remove(this.id);
                    }
                    setData(this.name, getCheckedValues($payment));
                });
            })();
            // LANGUAGE
            (function() {
                var $language = document.getElementById('language');

                $form.addListener('change', '#language input, #language select', function() {
                    if (this.type === 'checkbox') {
                        var $label = this.parentNode.querySelector('label');
                        var $img = this.parentNode.querySelector('img');

                        if (this.checked) {
                            Footer.criteria.add(this.id, {
                                text: $label.textContent,
                                type: ($img ? 'img' : 'text'),
                                icon: ($img ? $img.src : 'No Image')
                            });
                        } else {
                            Footer.criteria.remove(this.id);
                        }
                        setData(this.name, getCheckedValues($language));
                    } else {
                        if (this.type === 'select-one') {
                            var option = this.options[this.selectedIndex];
                        }
                        if (this.value && this.value != 0) {
                            Footer.criteria.add(this.id, {
                                text: this.dataset['selected'] ? this.dataset['selected'] : option.textContent,
                                type: 'img',
                                icon: this.dataset['flag'] ? this.dataset['flag'] : option.dataset['flag']
                            });
                        } else {
                            Footer.criteria.remove(this.id);
                        }
                        setData(this.name, this.value);
                    }
                });

                +function sortSelect()
                {
                    var $select = $language.querySelector('.country-select'),
                        $options,
                        $optionsContainer;

                    if (!$select) {
                        return;
                    }

                    $optionsContainer = $select.querySelector('.items');
                    $options = [].slice.call($select.querySelectorAll('option'));
                    $optionsContainer.innerHTML = '';

                    var option;
                    $options.forEach(function(opt) {
                        option = document.createElement('li');
                        option.dataset['value'] = opt.value;
                        option.textContent = opt.label;
                        $optionsContainer.appendChild(option);
                    });
                }();

            })();
            // CRITERIA
            (function() {
                $form.addListener('change', '#criteria .css-radio', function()
                {
                    var _item = this.parents('.item'),
                        _cell = _item && _item.querySelector('.cell'),
                        icon,
                        text;

                    text = _cell && _cell.textContent.trim();
                    icon = _cell && _cell.querySelector('i[class^="icon-"]');
                    icon = icon && icon.className;

                    if (this.checked) {
                        setData(this.name, this.value);
                        if (text && icon) {
                            Footer.criteria.add(this.name, {
                                text: text,
                                type: 'icon',
                                icon: icon
                            });
                        }
                    } else {
                        setData(this.name, '');
                        Footer.criteria.remove(this.name)
                    }
                });
            })();

            function getCheckedValues(scope) {
                scope = scope || $form;
                var $checked, dataTmp;
                $checked = scope.querySelectorAll('input:checked');
                dataTmp = [];

                for (var i = 0; i < $checked.length; i++) {
                    if ($checked[i].value != 'on' && dataTmp.indexOf($checked[i].value) == -1) {
                        dataTmp.push($checked[i].value);
                    }
                }
                return dataTmp;
            }

            //=================================================================
            var lastQuery = '', isSending = false, timeout;

            setInterval(function() {
                !isSending && sendData();
            }, 300);

            function sendData() {
                if (JSON.stringify(mainData) === lastQuery) {
                    return;
                }
                lastQuery = JSON.stringify(mainData);

                ajax(quiz.ajaxurl, {
                    method: 'POST',
                    data: mainData,
                    timeout: 5000,
                    beforeSend: function() {
                        isSending = true;
                    },
                    success: function (response) {
                        var tmp = [];
                        if (typeof response.full !== 'undefined') {
                            Matches.set(response.full, 'fully');
                            tmp.push('fully:' + response.full);
                        }
                        if (typeof response.partial !== 'undefined') {
                            Matches.set(response.partial, 'partially');
                            tmp.push('partially:' + response.partial);
                        }
                        isLocalStorageSupported() && tmp.length && (localStorage['bmr-quiz-matches'] = tmp.join('#'));
                    },
                    complete: function(xhr, status) {
                        status === 'timeout' && (lastQuery = '');
                        isSending = false;
                    },
                    error: function (xhr, status) {
                        console.warn('Error: %s\n', status, xhr);
                    },
                    json: true
                });
            }

            function setData(name, value) {
                var names = name.match(/([^\[\]]*)(?:\[(\w*)\])?/), isRemove;

                isRemove = (
                       value instanceof Array && !value.length
                    || value instanceof Object && !Object.size(value)
                    || value === ''
                );

                if (typeof names[2] !== 'undefined' && names[2] !== '') {
                    !mainData[names[1]] && (mainData[names[1]] = {});

                    if (!isRemove) {
                        mainData[names[1]][names[2]] = value;
                    } else {
                        delete mainData[names[1]][names[2]];
                        !Object.size(mainData[names[1]]) && delete mainData[names[1]];
                    }
                } else {
                    if (!isRemove) {
                        mainData[names[1]] = value;
                    } else {
                        delete mainData[names[1]];
                    }
                }
            }

            function setLoadingTitle(title) {
                $titleMain.textContent = title || 'Загрузка...';
            }

        })(); // < PARTIALS LOADER

    }); // < DOMContentLoaded

    function setMatches(matches)
    {
        matches = matches.split(',');
        var $items  = document.querySelectorAll('.criteria-item'),
            matchesCnt = 0;

        for (var i = $items.length; i--; ) {
            if ($items[i].dataset.criteria !== undefined && matches.indexOf($items[i].dataset.criteria) >= 0) {
                $items[i].classList.add('is-matched');
                matchesCnt++;
            }
            else {
                $items[i].classList.remove('is-matched');
            }
        }
        return matchesCnt;
    }

    window.setMatches = setMatches;

})(); // < script wrapper
