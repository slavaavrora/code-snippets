<?php get_header(); the_post();  ?>
<div class='content inner clearfix1 assistant' data-current-page="landing">
    <?php get_template_part('sidebars/menu', 'left')?>
    <div class="content-middle">
        <div class="container-address">
            <div class="container-address-header">
                <h1 class="page-header"><?php the_title(); ?></h1>
                <?php
                Base\Helpers\Main::breadcrumbs([
                    __('Букмекеры', 'bmr') => getSectionHomepageLink('bookmakers'),
                    get_the_title()        => get_permalink(),
                    ''                     => ''
                ]);
                ?>
            </div>
            <div class="container-address-info">
                <?php \Bmr\Assistant\Base::getTemplatePart('assistant', 'content'); ?>
                <?php \Bmr\Assistant\Base::getTemplatePart('assistant', 'footer'); ?>
            </div>
        </div>
    </div>
</div>
<?php get_footer() ?>
