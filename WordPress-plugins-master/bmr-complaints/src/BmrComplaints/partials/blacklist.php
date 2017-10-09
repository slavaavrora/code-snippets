<div class="wrap">
    <h2><?php echo esc_html(get_admin_page_title()) ?></h2>
    <br>
    <div class="row">
<?php
    $emailListTable = new BmrEmailListTable();
    $emailListTable->prepare_items();
?>
    <form id="email-actions" method="POST">
        <?php $emailListTable->display(); ?>
    </form>
    </div>
</div>

