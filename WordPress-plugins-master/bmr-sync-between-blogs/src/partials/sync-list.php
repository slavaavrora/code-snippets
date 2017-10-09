<div class="wrap">
    <h2><?php echo esc_html(get_admin_page_title()) ?></h2>
    <br>
    <div class="row">
        <?php
        $table = new \Bmr\Sync\Table();
        $table->prepare_items();
        ?>
        <form id="sync-actions" method="POST">
            <?php $table->display(); ?>
        </form>
    </div>
</div>

