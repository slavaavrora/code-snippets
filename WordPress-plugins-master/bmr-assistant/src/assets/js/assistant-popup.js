(function() {
    'use strict';

    var $assistant, $assistantContainer, $pageContent, $closeBtn, $link, speed, excluded;
    $assistantContainer = document.getElementById('assistant-popup');
    $assistant = document.querySelector('.assistant-popup');
    $pageContent = document.querySelector('.page-content');

    excluded = [
        'vse-bukmekerskie-kontory',
        'obzor-bukmekerskoy-kontory-william-hill',
        'obzor-bukmekerskoy-kontory-winlinebet',
        'obzor-bukmekerskoy-kontory-pari-match',
        'obzor-bukmekerskoy-kontory-liga-stavok',
        'luchshie-bukmekerskie-kontory',
        'supported_languages/russkij',
        'gambling_licences/russia-fns-gamling-licence'
    ];
    excluded = new RegExp('(' + excluded.join('|') + ')/?$');

    if (!$assistant || !$pageContent) {
        return;
    }

    $link = $assistant.querySelector('.assistant-popup-button');
    $closeBtn = $assistant.querySelector('.assistant-popup-close');
    speed = 300;

    $assistant.style.transitionDuration       = speed + 'ms';
    $assistant.style.webkitTransitionDuration = speed + 'ms';

    function open() {
        var openedTimes = docCookies.getItem('bmr-assistant-popup'), currUri;

        if (openedTimes == -1 || excluded.test(location.pathname)) {
            return;
        }

        currUri = bmr.getCurrentUri();
        openedTimes = openedTimes === null ? [] : openedTimes.split('|');
        openedTimes.indexOf(currUri) === -1 && openedTimes.push(currUri);

        if (openedTimes.length !== 3) {
            openedTimes !== 0 && docCookies.setItem('bmr-assistant-popup', openedTimes.join('|'), 3.15569e7, '/');
            return;
        }

        setTimeout(function() {
            pageHeader.disable();
            document.body.classList.add('assistant-is-appearing');
            $assistant.style.display = 'block';

            $assistantContainer.classList.add('popup-wrap');
            document.body.classList.add('assistant-popup-opened');

            setTimeout(function() {
                $assistant.classList.add('is-open');

                setTimeout(function() {
                    document.body.classList.remove('assistant-is-appearing');
                    pageHeader.enable();
                }, speed);
            }, 100);
        }, 2000);
    }

    function close() {
        setTimeout(function() {
            $assistant.classList.remove('is-open');

            setTimeout(function() {
                $assistant.style.display = 'none';
                document.body.classList.remove('assistant-is-appearing');
                $assistantContainer.classList.remove('popup-wrap');
                document.body.classList.remove('assistant-popup-opened');
            }, speed);
        }, 20);
    }

    addEvent(window, 'DOMContentLoaded', function() {
        $pageContent.appendChild($assistantContainer);
        open();
    });

    addEvent($closeBtn, 'click', function() {
        // Don't show for 1 month
        docCookies.setItem('bmr-assistant-popup', -1, 2.592e6, '/');
        close();
    });

    addEvent($link, 'click', function() {
        // Don't show for 3 month
        docCookies.setItem('bmr-assistant-popup', -1, 7.776e6, '/');
    });

})();