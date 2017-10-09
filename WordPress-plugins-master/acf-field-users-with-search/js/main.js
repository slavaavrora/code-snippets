(function($)
{
    $(document).on('acf/setup_fields', function(e, postbox)
    {
        if (typeof postbox[0] !== 'undefined') {
            postbox = postbox[0];
        }

        if (!(postbox instanceof Element)) {
            return;
        }

        var $els = postbox.querySelectorAll('.acf-users-with-search');
        for (var i = $els.length; i--; ) {
            (function(){
                var $container         = $els[i],
                    $users             = $container.querySelectorAll('.left .title'),
                    $selectedContainer = $container.querySelector('.right ul'),
                    $selectedTemplate  = $selectedContainer.querySelector('.template');

                $container.querySelector('input[name="acf-users_with_search"]').addEventListener('input', function()
                {
                    var value  = this.value.replace(/(^\s+)|(\s+$)/, ''),
                        regexp = new RegExp(value, 'i');

                    for (var j = $users.length; j--; ) {
                        $users[j].parentNode.classList[regexp.test($users[j].innerHTML) ? 'remove' : 'add']('disable');
                    }
                });

                $container.querySelector('.left').addEventListener('click', function(e)
                {
                    var $el = e.target;
                    while ($el !== this) {
                        if ($el.tagName === 'LI') {
                            $el.classList.add('selected');

                            var $newEl = $selectedTemplate.cloneNode(true);
                            $selectedContainer.appendChild($newEl);
                            $newEl.classList.remove('template');
                            $newEl.querySelector('.title').innerHTML = $el.querySelector('.title').innerHTML;
                            $newEl.querySelector('input').value = $el.dataset.id;

                            return;
                        }

                        $el = $el.parentNode;
                    }
                });

                $selectedContainer.addEventListener('click', function(e)
                {
                    var $el = e.target;
                    while ($el !== this) {
                        if ($el.tagName === 'LI') {
                            var id = $el.querySelector('input').value;
                            $container.querySelector('.left [data-id="' + id + '"]').classList.remove('selected');
                            $el.parentNode.removeChild($el);

                            return;
                        }

                        $el = $el.parentNode;
                    }
                });
            })();
        }
    });
})(jQuery);