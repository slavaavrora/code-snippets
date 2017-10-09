<?php
use Bmr\Assistant\Session;

/** @var array $data */
$data = Session::get('assistant_matches');

$criteria = $data['criteria'];
unset($data['criteria']);
$criteriaTotalCnt = count($criteria);

$query = null;
$fullyMatchedCnt = $partialMatchedCnt = 0;

if ($data) {
    $ids     = array_keys($data);
    $matched = wp_list_pluck($data, 'criteria', 'ID');
    $data    = wp_list_pluck($data, 'percent', 'ID');

    $query = new WP_Query([
        'post_type'      => 'bookreviews',
        'post__in'       => $ids,
        'posts_per_page' => -1
    ]);
    $posts      = [];

    global $post;
    while($query->have_posts()) {
        $query->the_post();

        $p           = $post;
        $p->match    = ceil((count($matched[$post->ID]) / $criteriaTotalCnt) * 100);
        $p->criteria = implode(',', $matched[$post->ID]);
        $p->priority = (int)get_field('about_bmr_order_priority', $post->ID);
        $p->rating   = (int)get_field('about_bmr_rating', $post->ID);
        $posts[]     = $p;
        $p->match < 100 ? $partialMatchedCnt++ : $fullyMatchedCnt++;
    }
    wp_reset_postdata();

    usort($posts, function($a, $b)
    {
        // Sort by match percent
        if ($a->match == $b->match) {
            // Sort by rating
            if ($a->rating === $b->rating) {
                // Sort by priority
                if ($a->priority === $b->priority) {
                    return 0;
                }
                return $a->priority < $b->priority ? 1 : -1;
            }
            return $a->rating < $b->rating ? 1 : -1;
        }
        return $a->match < $b->match ? 1 : -1;
    });
}
?>
<div class="assistant-results" id="results">
    <?php if ($posts): ?>
    <h2 class="heading-middle">
    <?php
        $txt = pluralForm(count($posts),[
            __('букмекерская контора', 'bmr'),
            __('букмекерские конторы', 'bmr'),
            __('букмекерских контор', 'bmr')
        ]);

        if (get_current_blog_id() === BLOG_ID_BMR_EN) {
            printf(
                '%d %s are%s suitable for you',
                $query->post_count,
                $txt,
                (!$fullyMatchedCnt ? ' ' . __('частично', 'bmr') : '')
            );
        } else {
            /* translators: Вам [частично] подходит 20 букмекерских контор */
            printf(
                __('Вам %s подходит %d %s', 'bmr'),
                (!$fullyMatchedCnt ? __('частично', 'bmr') : ''),
                $query->post_count,
                $txt
            );
        }
     ?>
    </h2>
    <div class="carousel" id="results-carousel" data-current="0">
        <ul class="carousel-list">
    <?php foreach($posts as $i => $post): setup_postdata($post); ?>
        <?php
        $dataTitle   = get_field('reviews_info_bmr', $post->ID);
        $dataLogo    = get_field('about_bmr_general_white_logo', $post->ID);
        $rating      = (int)get_field('about_bmr_rating', $post->ID);
        $betlink     = get_field('about_bmr_general_bet_key', $post->ID);
        $betlink     = $betlink ? '/visit/' . $betlink : false;
        $tag         = $betlink ? 'a' : 'span';
        $permalink   = get_permalink();
        $ratingStars = array_fill(0, $rating, '<i class="icon-star-01"></i>');
        $ratingStars = array_pad($ratingStars, 5, '<i class="icon-star-02"></i>');
        $ratingStars = implode(PHP_EOL, $ratingStars);
        ?>
            <li <?= $post->match < 100 ? 'class="secondary"' : '' ?>>
                <article class="item <?= !$dataLogo ? 'no-logo' : '' ?>" data-criteria="<?= $post->criteria ?>">
                    <h2 class="title"><?= ($i+1), '. ', $dataTitle ?></h2>
                    <?php if ($dataLogo): ?>
                    <div class="logo">
                        <img src="<?= $dataLogo ?>" alt="<?= $dataTitle ?>">
                    </div>
                    <?php endif; ?>
                    <div class="info">
                        <div class="rating">
                            <span class="title"><?php _e('Оценка', 'bmr') ?></span>
                            <span class="rate"><?= $ratingStars ?></span>
                            <span class="point"><?= $rating ?>/5</span>
                        </div>
                        <div class="match">
                            <span class="title"><?php _e('Совпадение', 'bmr') ?></span>
                            <span class="percent"><?= $post->match ?>%</span>
                        </div>
                    </div>
                    <div class="buttons">
                        <a class="review" href="<?= $permalink ?>"><?php _e('Смотреть обзор', 'bmr') ?></a>
                        <<?= $tag ?> class="bet <?= ($rating > 3 && $betlink ? '' : 'is-disabled') ?>" href="<?= $betlink ?>"><?php _e('Перейти на сайт', 'bmr') ?></<?= $tag ?>>
                    </div>
                </article>
            </li>
    <?php endforeach; ?>
        </ul>
        <!-- < .carousel-list -->

        <?php if($fullyMatchedCnt): ?>
        <div class="show-more full-match is-hidden">
            <span class="num">+<?= $fullyMatchedCnt ?></span>
            <p class="text">
                <?=
                \Bmr\Assistant\Helper::pluralForm($fullyMatchedCnt, [
                    __('подходящая контора', 'bmr'),
                    __('подходящие конторы', 'bmr'),
                    __('подходящих контор', 'bmr'),
                ])
                ?>
            </p>
        </div>
        <?php endif; ?>

        <?php if($partialMatchedCnt):?>
        <div class="show-more partial-match is-hidden">
            <span class="num">+<?= $partialMatchedCnt ?></span>
            <p class="text">
                <?php _e('частично', 'bmr') ?>
                <?=
                \Bmr\Assistant\Helper::pluralForm($partialMatchedCnt, [
                    __('подходящая контора', 'bmr'),
                    __('подходящие конторы', 'bmr'),
                    __('подходящих контор', 'bmr'),
                ])
                ?>
            </p>
        </div>
        <?php endif; ?>

        <div class="carousel-nav">
            <i class="prev icon-left"></i>
            <i class="next icon-right-01"></i>
        </div>
        <!-- < .carousel-nav -->
    </div>
    <!-- < .carousel -->

    <div class="results-footer">
        <h3 class="title" id="results-title">
        <?php
            /* translators: Совпадающие критерии с БК WILLIAM HILL: 3 ИЗ 3 */
            printf(
                __('Совпадающие критерии с БК <span class="bookmaker">%s</span>: <span class="num-matched">%d</span> из <span class="num-total">%d</span>', 'bmr'),
                '...', 0, $criteriaTotalCnt
            );
        ?>
        </h3>
        <a  class="button-default button-default-s again-btn" href="#product"><?php _e('Пройти снова!', 'bmr') ?></a>
        <div class="criteria-list">
            <?php foreach ($criteria as $key => $cri): ?>
            <?php
                $type = isset($cri['type']) ? $cri['type'] : 'icon';
             ?>
            <div class="criteria-item" data-criteria="<?= $key ?>">
                <div class="item-wrap">
                    <i class="status-icon"></i>
                    <?php if($type === 'img'): ?>
                        <img src="<?= $cri['icon'] ?>">
                    <?php elseif ($type === 'text'): ?>
                        <div class="icon"><?= $cri['icon'] ?></div>
                    <?php else: ?>
                        <div class="icon"><i class="<?= $cri['icon'] ?>"></i></div>
                    <?php endif; ?>
                    <div class="text"><?= $cri['text'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
    </div>

    <?php else: ?>
    <p class="posts-block-not-found"><?php _e('Записей не найдено.', 'bmr') ?></p>
    <?php endif; ?>
</div>