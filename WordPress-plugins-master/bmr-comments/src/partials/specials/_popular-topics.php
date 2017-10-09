<?php
$topics = \Bmr\Comments\Helper::getPopularTopics();
?>
<?php if ($topics): ?>
<div class="popular-topics-wrapper">
    <h2 class="popular-topics-heading"><?php _e('Популярные темы', 'bmr') ?></h2>
    <div class="popular-topics-container">
        <?php foreach ($topics as $topic): ?>
            <?php
                $author = \Bmr\Comments\Helper::getUserInfoByComment($topic->comment_ID);
                $content = strip_shortcodes($topic->comment_content);
                $content = strip_tags($content);
                $content = wp_trim_words($content, 20);
            ?>
            <div class="pt-item">
                <h3 class="pt-topic">
                    <a href="<?= get_permalink($topic->ID) ?>"><?= get_the_title($topic->ID); ?></a>
                </h3>
                <div class="pt-comment">
                    <div class="pt-meta">
                        <span class="pt-meta-item pt-comment-count">
                        <?=
                            $topic->count, ' ',
                            pluralForm($topic->count, [
                                __('Комментарий', 'bmr'),
                                __('Комментария', 'bmr'),
                                __('Комментариев', 'bmr')
                            ]);
                        ?>
                        </span>
                        <time class="pt-meta-item pt-comment-time">
                            <?php
                            printf(
                                __('%s назад', 'bmr'),
                                human_time_diff(date('U', strtotime($topic->last_date)), current_time('timestamp')
                                ));
                            ?>
                        </time>
                    </div>
                    <div class="pt-comment-inner">
                        <div class="pt-comment-avatar">
                            <?php if ($author->avatar && !$author->hasDefaultAvatar): ?>
                                <img
                                    data-role="user-avatar"
                                    src="<?= $author->avatar ?>"
                                    alt="<?php _e('Аватар', 'bmr') ?>"
                                    />
                            <?php else: ?>
                                <span class="pt-user-initials"><?= $author->initials ?></span>
                            <?php endif ?>
                        </div>
                        <div class="pt-comment-content">
                            <span class="pt-comment-author"><?= $author->name ?></span> –
                            <?= $content ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>