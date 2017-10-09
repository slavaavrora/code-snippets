<?php
get_header();
global $wp_query;

$postType = get_post_type();
$complaint_statuses = $postType == BmrConfig::POST_TYPE_KAPPER
    ? get_terms(BmrConfig::TAXONOMY_COMPLAINT_STATUS_KAPPER, 'hide_empty=0')
    : get_terms(BmrConfig::TAXONOMY_COMPLAINT_STATUS, 'hide_empty=0');

$bookmakers         = get_terms(BmrConfig::TAXONOMY_COMPLAINT_COMPANY, 'hide_empty=1');
$cplType            = $cplBookmaker = '';
$args               = array();

if (isset($_GET['complaint_type']) || isset($_GET['complaint_bookmaker'])) {
    $tax_query    = array();
    $meta_query   = array();
    $cplType      = null;
    $cplBookmaker = null;

    if (isset($_GET['complaint_type'])) {
        $cplType = sanitize_text_field($_GET['complaint_type']);
    }
    if (isset($_GET['complaint_bookmaker'])) {
        $cplBookmaker = sanitize_text_field($_GET['complaint_bookmaker']);
    }

    if ($cplType && $cplBookmaker) {
        $tax_query['relation'] = 'AND';
    }
    if ($cplType && $cplType != '*') {
        $tax_query[]       = array(
            'taxonomy' => $postType == BmrConfig::POST_TYPE_KAPPER
                    ? BmrConfig::TAXONOMY_COMPLAINT_STATUS_KAPPER
                    : BmrConfig::TAXONOMY_COMPLAINT_STATUS,
            'field'    => 'slug',
            'terms'    => $cplType
        );
        $args['tax_query'] = $tax_query;
    }
    if ($cplBookmaker && $cplBookmaker != '*') {
        $tax_query[]       = array(
            'taxonomy' => BmrConfig::TAXONOMY_COMPLAINT_COMPANY,
            'field'    => 'slug',
            'terms'    => $cplBookmaker
        );
        $args['tax_query'] = $tax_query;
    }

    $args         = array_merge($wp_query->query_vars, $args);
    $posts        = get_posts(array_merge($args, array('nopaging' => true)));
    $postsPerPage = get_query_var('posts_per_page', 15);
    $max          = ceil(count($posts) / $postsPerPage);
}
$max = isset($max) ? $max : $wp_query->max_num_pages;
$paged = (get_query_var('page') > 1) ? get_query_var('page') : 1;
$args['paged'] = $paged;
$args['max_num_pages'] = $max;

query_posts(array_merge($wp_query->query_vars, $args));

if (isset($wp_query->query[BmrConfig::TAXONOMY_COMPLAINT_STATUS])) {
    $cplType = $wp_query->query[BmrConfig::TAXONOMY_COMPLAINT_STATUS];
}
if (isset($wp_query->query[BmrConfig::TAXONOMY_COMPLAINT_STATUS_KAPPER])) {
    $cplType = $wp_query->query[BmrConfig::TAXONOMY_COMPLAINT_STATUS_KAPPER];
}

if (isset($wp_query->query[BmrConfig::TAXONOMY_COMPLAINT_COMPANY])) {
    $cplBookmaker = $wp_query->query[BmrConfig::TAXONOMY_COMPLAINT_COMPANY];
}

$archDesc = (is_post_type_archive(BmrConfig::POST_TYPE)
    ? BmrOptions::option('bmr_archive_header_txt')
    : term_description('', get_query_var('taxonomy'))
);

if (
    $postType == BmrConfig::POST_TYPE_KAPPER &&
    !empty(BmrOptions::option('bmr_archive_kapper_header_txt'))
) {
    $archDesc = BmrOptions::option('bmr_archive_kapper_header_txt');
}

$queriedObj = get_queried_object();
$title = isset($queriedObj->taxonomy) ? BmrHelper::getComplaintsTermPluralForm($queriedObj) : __('Все жалобы', 'bmr');

?>
<div class='content inner clearfix1 JAL'>
    <?php get_template_part('sidebars/menu', 'left')?>
    <?php get_template_part('sidebars/right')?>

    <div class="content-middle group">
        <div class="bmr-content has-infinite-scroll">
            <div class="container-address">
                <div class="container-address-header">
                    <h1 class="page-header"><?= $title ?></h1>
                    <?php
                    $crumbs = [];
                    if ($postType == BmrConfig::POST_TYPE_KAPPER) {
                        $crumbs[__('Прогнозы', 'bmr')] = getSectionHomepageLink('tips');
                        $crumbs[__('Капперские сайты', 'bmr')] = main_site_url() . '/kappers';
                        $crumbs[__('Жалобы', 'bmr')] = get_post_type_archive_link(BmrConfig::POST_TYPE_KAPPER);
                    } else {
                        $crumbs[__('Букмекеры', 'bmr')] = getSectionHomepageLink('bookmakers');
                        $crumbs[__('Жалобы', 'bmr')]    = get_post_type_archive_link(BmrConfig::POST_TYPE);
                    }
                    \Base\Helpers\Main::breadcrumbs($crumbs);
                     ?>
                </div>
                <!-- /.container-address-header -->
                <?php if (!empty($archDesc)): ?>
                <div class="inner-content-with-bg archive-description">
                    <div class="archive-description-content">
                        <?php echo $archDesc ?>
                    </div>
                    <div class="archive-read-more">
                        <a href="#" class="archive-read-more-btn" rel="nofollow"><?php _e('Развернуть описание', 'bmr') ?></a>
                    </div>
                </div>
                <?php else: ?>
                <br>
                <?php endif; //archive-description-content ?>
                <div class="menu-complains posts-manage-menu">
                    <div class="menuDropdown">
                        <div class="complaint-type-dropdown">
                            <select class="filter-dropdown" data-class="filter-dropdown fancy-select-bmr">
                                <option value="" selected disabled><?php _e('Выбрать тип жалобы', 'bmr') ?></option>
                                <option value="*"><?php _e('Все типы', 'bmr') ?></option>
                                <?php foreach($complaint_statuses as $complaint_status): ?>
                                    <option <?php selected($cplType, $complaint_status->slug) ?> value="<?php echo $complaint_status->slug; ?>"><?php echo $complaint_status->name ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <?php
                        if ($postType != BmrConfig::POST_TYPE_KAPPER) :
                        ?>
                        <div class="complaint-bookmaker-dropdown">
                            <select class="filter-dropdown" data-class="filter-dropdown fancy-select-bmr">
                                <option value="" selected disabled><?php _e('Выбрать букмекера', 'bmr') ?></option>
                                <option value="*"><?php _e('Все конторы', 'bmr') ?></option>
                                <?php foreach($bookmakers as $bookmaker):  ?>
                                    <option <?php selected($cplBookmaker, $bookmaker->slug) ?> value="<?php echo $bookmaker->slug ?>"><?php echo $bookmaker->name ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <?php
                        endif;
                        ?>
                    </div>
                    <div class="manage-view-menu">
                        <span class="change-view-btn posts-manage-menu-item active grid-btn" data-view-class="view-grid">
                            <i class="icon-new icon-grid"></i><?php _e('Сеткой', 'bmr') ?>
                        </span>
                        <span class="change-view-btn posts-manage-menu-item list-btn" data-view-class="view-list">
                            <i class="icon-new icon-list"></i><?php _e('Списком', 'bmr') ?>
                        </span>
                    </div>
                </div>
                <!-- /.menu-complaints -->
                <div class="posts-block-container">
                    <div class="posts-block changeable-view view-grid" id="infinitescroll_block">
                        <?php global $post; if (have_posts()) { ?>
                        <?php while (have_posts()): the_post(); ?>
                        <?php
                        $status        = wp_get_post_terms(get_the_ID(), BmrConfig::TAXONOMY_COMPLAINT_STATUS);
                        $status = !empty($status) ? $status : '';
                        $types         = wp_get_post_terms(get_the_ID(), BmrConfig::TAXONOMY_COMPLAINT_TYPE);
                        $types = !empty($types) ? $types : '';
                        $bookmaker         = wp_get_post_terms(get_the_ID(), BmrConfig::TAXONOMY_COMPLAINT_COMPANY);
                        $bookmaker = !empty($bookmaker) ? $bookmaker : '';
                        $views         = get_post_meta(get_the_ID(), 'views', true);
                        $commentsData = wp_count_comments(get_the_ID());
                        $commentsCount = $commentsData->approved;

                        $disputeCurrency = get_field('bmr_dispute_currency');
                        $disputeSum = get_field('bmr_dispute_sum');
                        $disputeSum = $disputeSum ?  BmrHelper::getNumberWithK($disputeSum) : false;

                        $termBg = get_field('bmr_term_bg', $status[0]);
                        $termBg = !empty($termBg) ? $termBg : '#000';
                        $postThumb    = wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()), 'single-post-thumbnail');
                        $postThumb    = $postThumb[0];
                        $background =  'style="background-' . (!empty($postThumb) ? 'image: url(' . $postThumb . ')' : 'color: ' . $termBg) . ';"';
                        $metaBg = empty($postThumb) ? 'style="background-color: ' . $termBg . ';"' : '';

                        $noImgClass = !empty($postThumb) ? 'has-image' : 'no-image';

                        if (!empty($status)) {
                            $statusLink = $postType == BmrConfig::POST_TYPE_KAPPER
                                ? get_term_link($status[0]->term_id, BmrConfig::TAXONOMY_COMPLAINT_STATUS_KAPPER)
                                : get_term_link($status[0]->term_id, BmrConfig::TAXONOMY_COMPLAINT_STATUS);
                            $statusLink = !empty($statusLink) ? $statusLink : '#';
                        }
                        if (!empty($types)) {
                            $typeLink = get_term_link($types[0]->term_id, BmrConfig::TAXONOMY_COMPLAINT_TYPE);
                            $typeLink = !empty($typeLink) ? $typeLink : '#';
                        }
                        ?>
                        <div class="block-item <?php echo $noImgClass ?>">
                            <div class="block-content ">
                                <div class="post-thumb">
                                    <a href="<?php echo get_permalink(get_the_ID()) ?>">
                                        <div class="image" <?php echo $background ?>>
                                            <div class="post-heading-container">
                                                <div class="post-heading">
                                                    <h2><?php echo $post->post_title ?></h2>
                                                </div>
                                            </div>
                                            <div class="image-gradient"></div>
                                        </div>
                                    </a>
                                    <?php if (!empty($status)) { ?>
                                        <a href="<?php echo $statusLink ?>">
                                            <span class="cat-badge" style="background-color: <?php echo $termBg; ?>">
                                                <?php echo $status[0]->name; ?>
                                            </span>
                                        </a>
                                    <?php } ?>
                                </div>
                                <!-- /.post-thumb -->
                                <div class="post-data">
                                    <a href="<?php echo get_permalink(get_the_ID()) ?>">
                                        <div class="post-heading-container">
                                            <div class="post-heading">
                                                <h2><?php echo $post->post_title ?></h2>
                                            </div>
                                        </div>
                                    </a>
                                    <div class="post-excerpt">
                                        <p class="text-fade"><?php echo wp_trim_words(get_the_content(), 100) ?></p>
                                    </div>
                                    <div class="post-meta" <?php echo $metaBg ?>>
                                        <span class="post-meta-views post-meta-item">
                                            <i class="icon-new icon-eye"></i>
                                            <?php echo $views ?>
                                        </span>
                                        <span class="post-meta-comments post-meta-item">
                                            <i class="icon-new icon-bubbles"></i>
                                            <?php echo $commentsCount ?>
                                        </span>
                                        <?php if ($disputeSum): ?>
                                         <span class="post-meta-comments post-meta-item">
                                            <i class="icon-new post-meta-currency"><?php echo $disputeCurrency ?></i>
                                             <?php echo $disputeSum ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <!-- /.post-data -->
                            </div>
                            <!-- /POST ITEM -->
                        </div><!-- /.block-item -->
                        <?php endwhile; ?>
                        <?php } else { ?>
                            <p class="posts-block-not-found"><?php _e('Записей не найдено.', 'bmr') ?></p>
                        <?php } ?>
                    </div>
                <!-- /.posts-block -->
                </div>
                <div class='load_more-block load_more-mainsite-block'>
                    <div class="load_more-comments load_more-mainsite">
                        <a class="load_more-comments-text" id="load-more" href="#">
                            <i class="icon-loop"></i>
                            <?php _e('Еще жалоб', 'bmr') ?>
                            <i class="icon-loop"></i>
                        </a>
                    </div>
                </div>
                <?php fw_paginate($max, $paged, 'desk');?>
            </div>
            <!-- /.container-adress -->
        </div>
    </div>
    <!-- /.content-middle -->
</div>
<!-- /.content.inner -->
<?php get_footer() ?>
