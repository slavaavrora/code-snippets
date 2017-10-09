<?php
    $relatedPosts = BmrRelated::getRelated(get_the_ID());
?>
<?php if (!empty($relatedPosts)) { ?>
<div class="difComplains">
    <h2><?= __('Похожие жалобы', 'bmr') ?></h2>
    <div class="images">
        <?php foreach($relatedPosts as $relatedPost) { ?>
            <?php
            ?>
            <a href="<?php echo get_permalink($relatedPost->ID) ?>">
                <?php
                    $status  = get_post_type() !== BmrConfig::POST_TYPE
                        ? wp_get_post_terms($relatedPost->ID, BmrConfig::TAXONOMY_COMPLAINT_STATUS_KAPPER)
                        : wp_get_post_terms($relatedPost->ID, BmrConfig::TAXONOMY_COMPLAINT_STATUS);

                    $statusSlug = !empty($status) ? $status[0]->slug : '';
                    $statusName = !empty($status) ? $status[0]->name : '';
                    unset($status);
                    $views         = get_post_meta($relatedPost->ID, 'views', true);
                    $commentsData  = wp_count_comments($relatedPost->ID);
                    $commentsCount = $commentsData->approved;
                ?>
                <div class="thumbnail <?php echo $statusSlug ?>">
                        <div class="noImage">
                            <span class="complaint-status"><?php echo $statusName; ?></span>
                            <div class="complaint-mobile-header">
                                <h2><?php echo wp_trim_words($relatedPost->post_title, 8) ?></h2>
                            </div>
                            <div class="post-meta">
                                <span class="post-meta-views post-meta-item">
                                    <i class="icon-new icon-eye"></i>
                                    <?php echo $views ?>
                                </span>
                                <span class="post-meta-comments post-meta-item">
                                    <i class="icon-new icon-bubbles"></i>
                                    <?php echo $commentsCount ?>
                                </span>
                            </div>
                        </div>
                    <p class="related-complaint-title text-fade"><?php echo $relatedPost->post_title; ?></p>
                </div>
            </a>
        <?php } ?>
    </div>
</div>
<?php } ?>