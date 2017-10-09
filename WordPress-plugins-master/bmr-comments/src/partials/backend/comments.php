<?php
    use Bmr\Comments\Helper;

    $statusMap = [
        'all'       => 'all',
        'moderated' => 'hold',
        'approved'  => 'approve',
        'trash'     => 'trash',
        'spam'      => 'spam'
    ];

    $status             = isset($_GET['comment_status']) ? $_GET['comment_status'] : 'approved';
    $commentsCount      = Helper::countComments(); // wp_count_comments()
    $commentsCount->all = $commentsCount->total_comments;
    $commentsPerPage    = 20;
    $currentPage        = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
    $search             = isset($_GET['s']) ? $_GET['s'] : '';
    $order              = isset($_GET['order']) ? $_GET['order'] : 'DESC';

    $commentsCount = array_map(function($v) {
       return number_format((float)$v, 0, '.', ' ');
    }, (array)$commentsCount);

    $args = [
        'status'  => $statusMap[$status],
        'number'  => $commentsPerPage,
        'search'  => $search,
        'order'   => $order,
        'orderby' => 'comment_date_gmt',
        'type'    => 'comment'
    ];

    $count      = get_comments(array_merge($args, ['count' => true, 'number' => -1]));
    $comments   = get_comments(array_merge($args, ['offset' => ($currentPage - 1) * $commentsPerPage]));
    $totalPages = ceil($count / $commentsPerPage);

    update_comment_cache($comments);
?>
<div class="wrap comments-wrap">
    <h2><?= get_admin_page_title() ?></h2>
    <?php settings_errors(); ?>

    <h2 class="nav-tab-wrapper">
        <a
            data-status="all"
            href="<?= add_query_arg('comment_status','all') ?>"
            class="nav-tab <?php Helper::navTabIsActive('all', $status) ?>"
        >
            <?php _e('Все', 'bmr') ?> <span class="count">(<?= $commentsCount['total_comments'] ?>)</span>
        </a>
        <a
            data-status="approved"
            href="<?= add_query_arg('comment_status','approved') ?>"
            class="nav-tab <?php Helper::navTabIsActive('approved', $status) ?>"
        >
            <?php _e('Одобренные', 'bmr') ?> <span class="count">(<?= $commentsCount['approved'] ?>)</span>
        </a>
        <a
            data-status="moderated"
            href="<?= add_query_arg('comment_status','moderated') ?>"
            class="nav-tab <?php Helper::navTabIsActive('moderated', $status) ?>"
        >
            <?php _e('Ожидающие', 'bmr') ?> <span class="count">(<?= $commentsCount['moderated'] ?>)</span>
        </a>
        <a
            data-status="spam"
            href="<?= add_query_arg('comment_status','spam') ?>"
            class="nav-tab <?php Helper::navTabIsActive('spam', $status) ?>"
        >
            <?php _e('Спам', 'bmr') ?> <span class="count">(<?= $commentsCount['spam'] ?>)</span>
        </a>
        <a
            data-status="trash"
            href="<?= add_query_arg('comment_status','trash') ?>"
            class="nav-tab <?php Helper::navTabIsActive('trash', $status) ?>"
        >
            <?php _e('Корзина', 'bmr') ?> <span class="count">(<?= $commentsCount['trash'] ?>)</span>
        </a>
    </h2>
    <form id="comments-form" method="get">
        <div class="quick-action">
            <div class="quick-action-left">
                <label><input type="checkbox" id="cb-select-all" /></label>
                <div class="quick-action-buttons">
                    <span id="checked-approve" data-action="approve">
                        <i class="icon-check"></i><?php _e('Одобрить', 'bmr') ?>
                    </span>
                    <span id="checked-spam" data-action="spam">
                        <i class="icon-black-list-bookmakers"></i><?php _e('Спам', 'bmr') ?>
                    </span>
                    <span id="checked-trash" data-action="trash">
                        <i class="icon-trash"></i><?php _e('Удалить', 'bmr') ?>
                    </span>
                </div>
                <input name="s" type="search" id="search" placeholder="<?php _e('Поиск', 'bmr') ?>" value="<?= $search ?>" />
            </div>
            <div class="quick-action-right">
                <select id="comments-sort" class="comments-sort-select">
                    <option value="DESC" <?php selected($order, 'DESC') ?>><?php _e('Новые', 'bmr') ?></option>
                    <option value="ASC" <?php selected($order, 'ASC') ?>><?php _e('Старые', 'bmr') ?></option>
                </select>
            </div>
        </div>

        <div id="comments-container" class="is-ajax">
            <ul id="comments-list">
                <?php
                foreach ($comments as $comment) {
                    include '_single-comment.php';
                }
                ?>
            </ul>
            <?php if ($totalPages > 1 && $currentPage < $totalPages): ?>
            <div class="pagination">
                <button
                    id="comments-load-more"
                    type="button"
                    class="load-more-btn"
                    data-total-pages="<?= $totalPages ?>"
                    data-current-page="<?= $currentPage ?>"
                >
                    <?php _e('Загрузить еще', 'bmr') ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </form>

    <form id="edit-form" class="comment-form" role="form">
        <?php
        $quicktags = ['buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,close'];
        wp_editor(
            '',
            'content',
            [
                'media_buttons' => false,
                'tinymce' => false,
                'quicktags' => $quicktags,
                'editor_height' => 250
            ]
        );
        ?>
        <button type="button" class="comment-btn cancel-btn"><?php _e('Отменить', 'bmr') ?></button>
        <button type="submit" class="comment-btn save-btn"><?php _e('Сохранить', 'bmr') ?></button>
    </form>

    <form id="reply-form" class="comment-form" action="<?= site_url('/wp-comments-post.php'); ?>" method="post" role="form">
        <?php
        $quicktags = ['buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,close'];
        wp_editor(
            '',
            'comment',
            [
                'media_buttons' => false,
                'tinymce' => false,
                'quicktags' => $quicktags,
                'editor_height' => 250
            ]
        );
        ?>
        <input type="hidden" name="comment_post_ID" id="comment_post_ID" value="">
        <input type="hidden" name="comment_parent" id="comment_parent" value="">
        <?php wp_comment_form_unfiltered_html_nonce(); ?>
        <button type="button" class="comment-btn cancel-btn"><?php _e('Отменить', 'bmr') ?></button>
        <button type="submit" class="comment-btn save-btn"><?php _e('Ответить') ?></button>
    </form>

</div><!-- /.wrap -->
