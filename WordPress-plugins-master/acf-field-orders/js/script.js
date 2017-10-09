window.addEventListener('DOMContentLoaded', function()
{
    var $containers = document.querySelectorAll('.acf-field-orders .container');

    for (var i = $containers.length; i--; ) {
        +function($container)
        {
            var $fake = $container.querySelector('.fake-item'),
                $items = $container.querySelectorAll('.item'),
                $activeNow,
                startTop = 0,
                startY,
                startX;

            if (!$fake || !$items.length) {
                return;
            }

            for (var i = $items.length; i--; ) {
                $items[i].addEventListener('mousedown', function(e)
                {
                    startTop = this.offsetTop;
                    $activeNow = this;
                    this.classList.add('moving');
                    $container.insertBefore($fake, this);
                    $fake.classList.remove('hide');

                    $activeNow.style.top = startTop + 'px';
                    startY = e.clientY;
                    startX = e.clientX;
                });

                $items[i].addEventListener('mousemove', function(e)
                {
                    if ($activeNow) {
                        var h = this.offsetHeight;
                        if (e.offsetY >= h / 2) { // after
                            $container.insertBefore($fake, this);
                        } else { // before
                            var $next = this.nextElementSibling;
                            $next ? $container.insertBefore($fake, $next) : $container.appendChild($fake);
                        }
                    }
                });
            }

            window.addEventListener('mouseup', function()
            {
                if ($activeNow) {
                    $fake.classList.add('hide');
                    $activeNow.classList.remove('moving');
                    $container.insertBefore($activeNow, $fake);
                    $activeNow = null;
                }
            });

            window.addEventListener('mousemove', function(e)
            {
                if ($activeNow) {
                    $activeNow.style.top = (startTop + e.clientY - startY) + 'px';
                    $activeNow.style.left = (e.clientX - startX) + 'px';
                }
            });
        }($containers[i]);
    }
});