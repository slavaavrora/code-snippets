(function() {
    "use strict";

    addEvent(window, 'DOMContentLoaded', function()
    {
        var $form = document.getElementById('sync-actions'),
            ids = [],
            interval,
            isProcesing = false,
            action,
            postType;

        if (!$form) {
            return;
        }

        // Detect current page post type
        postType = location.search.match(/post_type=([^&]*)/);
        postType && (postType = postType[1]);

        addEvent('#doaction, #doaction2', 'click', function(e) {
            var sel = 'bulk-action-selector-' + (this.id === 'doaction' ? 'top' : 'bottom');
            action = document.getElementById(sel).value;
        });

        addEvent($form, 'submit', function(e) {
            e.preventDefault();

            if (isProcesing || action !== 'sync') {
                return;
            }

            ids = getCheckedPostIds();
            processSync();
            interval = setInterval(processSync, 1500);
        });

        function processSync()
        {
            if (isProcesing) {
                return;
            }
            var id = ids.pop();
            if (id) {
                syncPost(id);
            } else {
                clearInterval(interval);
            }
        }

        var isTransfer = !!location.search.match('&transfer');
        function syncPost(id)
        {
            var $row = document.getElementById('status-' + id);
            $row = $row ? $row.parents('tr') : null;
            $row.style.backgroundColor = '';
            document.body.scrollTop = $row.offsetTop;

            ajax(ajaxurl, {
                data: {
                    action: 'sync-post',
                    post_type: postType,
                    status: '',
                    transfer: isTransfer,
                    post_id: id
                },
                beforeSend: function() {
                    isProcesing = true;
                    $row.style.backgroundColor = '#F5FB94';
                    setStatus(id, bmrSync.status.processing);
                },
                success: function(response) {
                    if (response.success) {
                        $row && $row.parentNode.removeChild($row);
                    } else {
                        var message = response.error ? ' (' + response.error + ')' : '';
                        setStatus(id, bmrSync.status.error + message);
                        console.warn(response.error);
                        $row && ($row.style.backgroundColor = '#F00');
                    }
                },
                error: function(xhr, status) {
                    setStatus(id, bmrSync.status.error);
                    console.warn(status);
                    $row && ($row.style.backgroundColor = '#F00');
                },
                complete: function() {
                    isProcesing = false;
                },
                json : true
            });
        }

        function setStatus(id, status)
        {
            var elem = document.getElementById('status-' + id);
            elem && (elem.textContent = status);
        }

        function getCheckedPostIds()
        {
            var checked = $form.querySelectorAll('[name="posts[]"]:checked'), i, ids = [];
            if (!checked.length) {
                return [];
            }
            for (i = checked.length; i--;) {
                ids.push(parseInt(checked[i].value));
            }
            return ids;
        }

    });

})();