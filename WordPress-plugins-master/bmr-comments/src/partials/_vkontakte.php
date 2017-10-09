<!-- Put this div tag to the place, where the Comments block will be -->
<div id="vk_comments"></div>
<script type="text/javascript">
    window.addEventListener('load', function() {
        var $vkCnt = document.querySelector('#comments [data-tab="vk"] .comments-count');
        if (!$vkCnt) {
            return;
        }

        VK.Widgets.Comments("vk_comments", {
            limit: 10,
            width: "500",
            attach: "*",
            onChange: function(cnt) {
                $vkCnt.textContent = cnt;
            }
        });
    });
</script>