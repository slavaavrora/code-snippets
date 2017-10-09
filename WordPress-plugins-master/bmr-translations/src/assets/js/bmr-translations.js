window.addEvent = function()
{
    var selector = arguments[0],
        parent   = arguments.length > 3 ? arguments[1] : document,
        event    = arguments[arguments.length > 3 ? 2 : 1],
        fn       = arguments[arguments.length > 3 ? 3 : 2];

    if ((typeof selector !== 'string' && typeof selector !== 'object')
        || typeof parent !== 'object' || typeof event !== 'string' || typeof fn !== 'function'
    ) {
        console.warn('Invalid arguments');
        return false;
    }

    var els = typeof selector === 'object'
        ? (selector instanceof NodeList || selector instanceof Array ? selector : [selector])
        : parent.querySelectorAll(selector);

    for (var i = els.length; i--; els[i] && els[i].addEventListener(event, fn));
    return true;
};

(function() {
    'use strict';

   addEvent(window, 'DOMContentLoaded', function() {

       // > PARTIALS LOADER
       //**********************************************


   }); // < DOMContentLoaded

})(); // < script wrapper


jQuery( document ).ready(function( $ ) {

    $('#lang-select').on('change', function() {
        location.search = location.search.replace(/\?(page=translations).*/, '$1&lang=' + this.value);
    });

    $('.js-generate-translation').click(function() {
        var self = this;
        var $spinners = $('.icon-loop', self);

        $.ajax({
            url: bmrTranslations.ajaxurl,
            data: {
                action: 'generate_translation',
                lang: $('#lang-select').val()
            },
            beforeSend: function() {
                $spinners.show();
            },
            complete: function() {
                $spinners.hide();
            }
        });
    });

    $('.js-translation-change').change(function() {
        var id = $(this).attr('name').match(/\d+/)[0];
        var translation = this.value;
        var self = this;

        var data = {
            action: 'save_translation',
            translation_id: id,
            translation: translation,
            lang: $('#lang-select').val()
        };

        $.ajax({
            url: bmrTranslations.ajaxurl,
            data: data,
            method: 'POST',
            dataType: 'json',
            beforeSend: function() {
                $(self).next('.helper').html('');
                $(self).prev().prev().find('i').show();
            },
            success: function(response) {
                if (!response.success) {
                    $(self).next('.helper').html('<span class="error-text">Error!</span>');
                }
            },
            complete: function(jqXHR, status) {
                if (status !== 'success') {
                    $(self).next('.helper').html('<span class="error-text">Error!</span>');
                }
                $(self).prev().prev().find('i').hide();
            }
        });
    });
});

