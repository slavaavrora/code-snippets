<?php
get_header();
the_post();

$postType = get_post_type();
$isKap = get_post_type() !== BmrConfig::POST_TYPE;

$views    = get_post_meta(get_the_ID(), 'views', true);
$status = $isKap
    ? wp_get_post_terms(get_the_ID(), BmrConfig::TAXONOMY_COMPLAINT_STATUS_KAPPER)
    : wp_get_post_terms(get_the_ID(), BmrConfig::TAXONOMY_COMPLAINT_STATUS);
$types     = wp_get_post_terms(get_the_ID(), BmrConfig::TAXONOMY_COMPLAINT_TYPE)?:[];
$companyTags = wp_get_post_terms(get_the_ID(), BmrConfig::TAXONOMY_COMPLAINT_COMPANY);
$tags     = wp_get_post_terms(get_the_ID(), BmrConfig::TAXONOMY_COMPLAINT_TAG);
$typeName = !empty($types) ? $types[0]->name : '';
$typeLink = !empty($types) ? get_term_link($types[0]->term_id, BmrConfig::TAXONOMY_COMPLAINT_TYPE) : '';
$isFirst = true;

$disputeCurrency = get_field('bmr_dispute_currency');
$disputeSum = get_field('bmr_dispute_sum');
$disputeSum = $disputeSum ?  BmrHelper::getNumberWithK($disputeSum) : false;
?>

    <div class='content inner JAL'>
        <?php get_template_part('sidebars/menu', 'left') ?>
        <?php get_template_part('sidebars/right') ?>

        <div class="content-middle clearfix1">
            <div class="bmr-content">
            <div class="bmrHeader">
                <div class="complaint-header">
                    <h1 class="page-header"><?php the_title() ?></h1>
                    <?php
                        $crumbs = [];
                        if ($isKap) {
                            $crumbs[__('Капперы', 'bmr')]         = getSectionHomepageLink('kappers');
                            $crumbs[__('Капперские сайты', 'bmr')] = get_post_type_archive_link('kappers');
                            $crumbs[__('Жалобы', 'bmr')] = get_post_type_archive_link(BmrConfig::POST_TYPE_KAPPER);
                        } else {
                            $crumbs[__('Букмекеры', 'bmr')] = getSectionHomepageLink('bookmakers');
                            $crumbs[__('Жалобы', 'bmr')] = get_post_type_archive_link(BmrConfig::POST_TYPE);
                        }

                        if (!empty($typeName)) {
                            $crumbs[$typeName] = $typeLink;
                        }

                        \Base\Helpers\Main::breadcrumbs($crumbs);
                    ?>
                    <div class="complaint-meta">
                        <?php if ($disputeSum): ?>
                            <span class="complaint-meta-text single-post-dispute">
                                <strong class="dispute-sum-label"><?php _e('Сумма спора:', 'bmr') ?></strong> <i class="post-meta-currency"><?php echo $disputeCurrency ?></i>
                                <?php echo $disputeSum ?>
                            </span>
                        <?php endif; ?>
                        <span class="reviews">
                            <span class="complaint-meta-text single-post-views-counter"><?php echo $views; ?></span>
                        </span>
                        <time>
                            <span class="complaint-meta-text single-post-date"><?php the_time('d.m.y') ?></span>
                        </time>
                    </div>
                    <div class="line_separator"></div>
                    <!-- /.complaint-meta  -->
                    <div class="socialBlock clearfix1">
                        <?php if (!empty($status)) {
                            $term_link = $isKap
                                ? get_term_link($status[0]->term_id, BmrConfig::TAXONOMY_COMPLAINT_STATUS_KAPPER)
                                : get_term_link($status[0]->term_id, BmrConfig::TAXONOMY_COMPLAINT_STATUS)
                            ?>
                            <a class="complaint-status-header-link" href="<?= $term_link?>"><div class="reshenie">
                                <span class="reshenie-icon <?php echo $status[0]->slug ?>"></span>
                                <span class="text"> <?php echo $status[0]->name ?></span>
                            </div></a>
                        <?php } ?>
                        <div class="social_links social_links-bottom social_links-ipad">
                            <?php fruitframe_template_part('share') ?>
                        </div>

                    </div>
                </div>
                <!-- /.complaint-header  -->
            </div>
            <!-- /header -->
            <div class="content complaint-content">
                <p class="spelling-notice"><span class="icon-info4"></span><?php _e('Орфография и пунктуация автора жалобы сохранены.', 'bmr') ?></p>
                <div class="use-default-ui"><?php the_content(); ?></div>
            </div>
            <!-- /.content  -->
            <?php if ($conclusion = get_field('bmr_conclusion')): ?>
                <div class="content com_rb use-default-ui">
                    <h4><?php _e('Комментарий РБ:', 'bmr') ?></h4>
                    <?php echo $conclusion; ?>
                </div>
                <!-- /.com_rb -->
            <?php endif; ?>

            <div class="post-meta-container">
                <?php if (!empty($types)) { ?>
                    <div class="published-in">
                        <em><?php _e('Опубликовано в:', 'bmr') ?> </em>
                        <?php foreach ($types as $term) {?>
                            <?php echo ($isFirst ? '' : ', ') ?><a href="<?php echo esc_url(get_term_link($term, BmrConfig::TAXONOMY_COMPLAINT_TYPE)) ?>" title="<?php echo sprintf( __( "View all posts in %s" ), $term->name);  ?>"><?php echo $term->name ?></a>
                            <?php $isFirst = false; } ?>
                    </div>
                <?php } ?>
                <?php if (!empty($companyTags) || !empty($tags)) { ?>
                    <div class="tags">
                        <em><?php _e('Теги:', 'bmr') ?> </em>
                        <?php foreach ($companyTags as $tag) { ?>
                            <a href="<?php echo esc_url(get_term_link($tag, BmrConfig::TAXONOMY_COMPLAINT_COMPANY)) ?>" rel="tag"><?php echo $tag->name ?></a>
                        <?php } unset($tag); ?>
                        <?php foreach ($tags as $tag) { ?>
                            <a href="<?php echo esc_url(get_term_link($tag, BmrConfig::TAXONOMY_COMPLAINT_TAG)) ?>" rel="tag"><?php echo $tag->name ?></a>
                        <?php } ?>
                    </div>
                <?php } ?>
                <div class="social_links social_links-bottom social_links-ipad">
                    <?php fruitframe_template_part('share') ?>
                </div>
            </div>
            <!-- /.POST META -->

            <!-- Обсуждение жалобы -->
            <?php include_once dirname(
                    __FILE__
                ) . '/../partials/comment-form.php'; ?>

            <!-- Похожие жалобы -->
            <?php include_once dirname(
                    __FILE__
                ) . '/../partials/complaint-related.php'; ?>

			<?php //Баннер ссылки на страницу формы вопросов
            if (function_exists('bmr_show_questions_banner')) {
                bmr_show_questions_banner();
            } ?>
            <div id="comments" class="comments-area">
                <?php comments_template() ?>
                <div class="clearfix"></div>
            </div>
        </div>
        <!-- /.content-middle -->
        </div>
    </div><!-- /.content .inner .megaClear1 -->
<?php get_footer(); ?>