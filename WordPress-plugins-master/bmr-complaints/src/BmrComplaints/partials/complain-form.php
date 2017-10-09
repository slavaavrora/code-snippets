<?php /** @var array $types */
global $privacyPolicy, $termsOfUse;
?>
<div class="complaint-form-wrapper">
<?php if (is_user_logged_in()) : ?>
    <div class="complaint-form-container">
        <div  class="headInfoForm inner-content-with-bg use-default-ui">
            <?php echo BmrOptions::option('bmr_form_header_txt') ?>
        </div>
        <form role="form" id="bmr-complaint-form" action="<?php echo admin_url('admin-ajax.php') ?>" method="post" class="bmrForm inner-content-with-bg form">
            <div id="summary" class="hidden headInfoForm">
                <h4></h4>
                <p></p>
            </div>
            <div class="main-inputs">
                <div class="form-row">
                    <label for="bmr_name"><?php _e('Ваше имя', 'bmr') ?><span class="bmr-notice"></span></label>
                    <input type="text" name="bmr_name" class="input-control" id="bmr_name"
                           placeholder="<?php _e('Введите ваше имя', 'bmr') ?>" value=""
                           required>
                </div>
                <div class="form-row">
                    <label for="bmr_username"><?php _e('Логин в букмекерской конторе', 'bmr') ?><span class="bmr-notice"></span></label>
                    <input type="text" name="bmr_username" class="input-control" id="bmr_username"
                           placeholder="<?php _e('Введите ваш логин в конторе', 'bmr') ?>" value="" required>
                </div>
                <div class="form-row">
                    <label for="bmr_email"><?php _e('E-Mail', 'bmr') ?><span class="bmr-notice"></span></label>
                    <input type="email" name="bmr_email" class="input-control" id="bmr_email"
                           placeholder="<?php _e('Введите ваш e-mail', 'bmr') ?>" value="<?php echo esc_attr($usrEmail) ?>"
                           required>
                </div>
                <div class="form-row">
                    <label for="bmr_complaint_type"><?php _e('Тип спора', 'bmr') ?><span class="bmr-notice"></span></label>
                    <select data-placeholder="<?php _e('Что произошло?', 'bmr') ?>" name="bmr_complaint_type" class="chosen-select" id="bmr_complaint_type" required>
                        <option value=""></option>
                        <?php foreach ($types as $type) { ?>
                            <option value="<?php echo esc_attr($type->term_id) ?>"><?php echo $type->name ?></option>
                        <?php } // end foreach $types ?>
                    </select>
                </div>
                <div class="form-row">
                    <label for="bmr_bookmaker"><?php _e('Букмекерская контора', 'bmr') ?><span class="bmr-notice"></span></label>
                    <input type="text" name="bmr_bookmaker" class="input-control bmr-bookmaker-input" id="bmr_bookmaker"
                           placeholder="<?php _e('Введите название конторы', 'bmr') ?>" value=""
                           required>
                </div>
                <div class="form-row">
                    <label for="bmr_support"><?php _e('Служба поддержки', 'bmr') ?><span class="bmr-notice"></span></label>
                    <div class="radio radio-inline">
                        <input id="bmr_support_yes" type="radio" name="bmr_support" value="yes" required>
                        <label for="bmr_support_yes" class="radio-label"><?php _e('Обращался', 'bmr') ?></label>
                        <input id="bmr_support_no" type="radio" name="bmr_support" value="no" required checked>
                        <label for="bmr_support_no" class="radio-label"><?php _e('Не обращался', 'bmr') ?></label>
                    </div>
                </div>
                <div class="form-row">
                    <label for="bmr_dispute_sum"><?php _e('Сумма спора', 'bmr') ?><span class="bmr-notice"></span></label>
                    <input type="text" pattern="\d+([.,]\d+)?" name="bmr_dispute_sum" class="input-control" id="bmr_dispute_sum"
                           placeholder="<?php _e('Введите сумму спора', 'bmr') ?>" value=""
                           required>
                </div>
                <div class="form-row">
                    <label for="bmr_dispute_currency"><?php _e('Валюта', 'bmr') ?><span class="bmr-notice"></span></label>
                    <select data-placeholder="<?php _e('Валюта', 'bmr') ?>" name="bmr_dispute_currency" class="chosen-select" id="bmr_dispute_currency" required>
                        <option value=""></option>
                        <?php foreach ($currencies as $code => $label) { ?>
                            <option value="<?php echo esc_attr($code) ?>"><?php echo $code . ' ' . $label ?></option>
                        <?php } // end foreach $currencies ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <label for="bmr_description"><?php _e('Описание спора', 'bmr') ?><span class="bmr-notice"></span></label>
                <textarea class="input-control" name="bmr_description" id="bmr_description" rows="6"
                          placeholder="<?php _e('Опишите вашу проблему в подробностях', 'bmr') ?>" required></textarea>
            </div>

            <div class="form-row">
                <input name="bmr_attachments" id="bmr_attachments" type="hidden" value="" />
                <label for="bmr_file" class="bmr_file"><i class="icon-new icon-icon7 bmr-paperclip"></i> <?php _e('Прикрепить файл', 'bmr') ?></label>
                <input type="file" class="form-control" id="bmr_file" name="bmr_file">
            </div>
            <div class="file-error">
                <span class="icon-Flaticon_23628"></span>
                <span class="file-error-msg"></span>
            </div>
            <div class="fileLoadedPARENT hidden">
                <div class="progressBar">
                    <div class="progressBar-inner"></div>
                    <div class="fileLoaded"></div>
                </div>
                <div class="cancelFile"><span class="file-action-txt"><?php _e('Отменить', 'bmr') ?></span></div>
            </div>
            <div class="fileArchive hidden"></div>

            <input type="hidden" name="bmr_user_id" value="<?php echo $userId ?>"/>

            <span class="requireField"><strong>*</strong> - <?php _e('Поля, обязательные для заполнения', 'bmr') ?></span>

            <div class="form-item-checkbox conditions-container">
                <input name="conditions" id="complaint-conditions" type="checkbox" class="login-checkbox-inp">
                <label for="complaint-conditions" class="conditions-label checkbox">
                    <?php
                        printf(__('Я согласен с <a href="%s" target="_blank">Условиями использования сайта</a> и <a href="%s" target="_blank">Политикой конфиденциальности</a>', 'bmr'), $termsOfUse, $privacyPolicy);
                    ?>
                </label>
            </div>

            <div class="theButton">
                <button type="submit" id="complaint-submit-btn" class="button"><?php _e('Отправить', 'bmr') ?></button>
            </div>
        </form>
    </div>
    <div id="related-complaints" class="related-complaints-list" style="display: none;">
        <h2 class="related-complaints-header"><?php _e('Похожие жалобы:', 'bmr') ?></h2>
    </div>

<?php else: ?>
    <div  class="headInfoForm inner-content-with-bg">
        <?php echo BmrOptions::option('bmr_form_header_txt') ?>
    </div>
    <?php include_once "complain-form-auth.php"; ?>
    <div class="saveEars inner-content-with-bg">
        <img src="<?php echo get_template_directory_uri() ?>/assets/img/babaear.jpg" alt=""/>
        <div class="saveEarsText">
            <?php echo BmrOptions::option('bmr_form_footer_txt') ?>
        </div>
    </div>
    <?php $recentPosts = BmrRelated::getRecent(7); ?>
    <?php if(!empty($recentPosts)) { ?>
    <div id="related-complaints" class="related-complaints-list">
        <h2 class="related-complaints-header"><?php _e('Новые жалобы:', 'bmr') ?></h2>
        <?php foreach($recentPosts as $post) { ?>
            <?php
            $link      = get_permalink($post->ID);
            $status    = wp_get_post_terms($post->ID, BmrConfig::TAXONOMY_COMPLAINT_STATUS);
            $status    = $status[0];
            $type      = wp_get_post_terms($post->ID, BmrConfig::TAXONOMY_COMPLAINT_TYPE);
            $type      = $type[0];
            $date      = get_the_time("d.m.y", $post->ID);
            $type_link = get_term_link($type->term_id, BmrConfig::TAXONOMY_COMPLAINT_TYPE);
            ?>
            <div class="single-complaint <?php echo $status->slug ?> inner-content-with-bg">
                <span class="image-category"><?php echo $status->name ?></span>
                <h2><a href="<?php echo $link; ?>"><?php echo $post->post_title ?></a></h2>
                <div class="date">
                    <span class="icon-calendar4 mainsite-inline"></span>
                    <span class="mainsite-italic mainsite-inline"> <?php echo $date ?></span>
                </div>
                <div class="complaint-content">
                    <p><?php echo wp_trim_words($post->post_content, 22); ?></p>
                </div>
                <div class="category-block">
                    <?php if(!is_wp_error($type_link)): ?>
                    <a href="<?php echo $type_link ?>"><span class="category"><?php echo $type->name ?></span><span></span></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php } ?>
    </div>
    <?php } ?>
<?php endif; ?>
</div>

