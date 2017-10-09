<?php
    use Bmr\Comments\Helper;
    $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
?>
<div class="wrap">
    <h2><?= get_admin_page_title() ?></h2>
    <?php settings_errors(); ?>

    <h2 class="nav-tab-wrapper">
        <a
            href="<?= add_query_arg('tab','general') ?>"
            class="nav-tab <?php Helper::navTabIsActive('general', $activeTab) ?>"
        >
            <?php _e('Общие', 'bmr') ?>
        </a>
        <a
            href="<?= add_query_arg('tab','moderators') ?>"
            class="nav-tab <?php Helper::navTabIsActive('moderators', $activeTab) ?>"
        >
            <?php _e('Модераторы', 'bmr') ?>
        </a>
        <a
            href="<?= add_query_arg('tab','blacklist') ?>"
            class="nav-tab <?php Helper::navTabIsActive('blacklist', $activeTab) ?>"
        >
            <?php _e('Черный список', 'bmr') ?>
        </a>
    </h2>

    <?php
        if ($activeTab == 'blacklist') {
            $table = new \Bmr\Comments\Blacklist();
            $table->prepare_items();
            ?>
            <form method="post">
                <?php $table->display(); ?>
            </form>
            <?php
        } elseif ($activeTab === 'moderators') {
            $table = new \Bmr\Comments\Moderators();
            $table->prepare_items();
            ?>
            <form method="post">
            <?php $table->display(); ?>
            </form>
            <?php
        } else {
            ?><form method="post" action="<?= admin_url('options.php'); ?>"><?php
                settings_fields(BMR_COMMENTS_SLUG . '-settings');
                do_settings_sections(BMR_COMMENTS_SLUG . '-settings');
                submit_button();
            ?></form><?php
        }
    ?>
</div><!-- /.wrap -->