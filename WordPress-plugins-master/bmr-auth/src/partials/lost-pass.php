<?php use Bmr\Auth\Helper; ?>
<div class="user-login-block mfp-hide reset-pass-form" id="recover-form">
    <div class="background">
        <div class="user-login-header">
            <h3><?= __('Забыли пароль?', 'bmr') ?></h3>
        </div>
        <div class="bmr-loading-spinner on-wp-login-form"></div>
        <?php do_action('lost_password'); ?>
        <form name="lostpasswordform" id="lostpasswordform" action="<?php echo esc_url( network_site_url( 'wp-login.php?action=lostpassword', 'login_post' ) ); ?>" method="post">
            <p class="form-info summary"></p>
            <p class="form-info"><?= __('Пожалуйста, введите ваш e-mail, Вы получите письмо со ссылкой для создания нового пароля', 'bmr') ?></p>
            <label for="user_login" ><?= __('E-Mail') ?></label>
            <input type="email" name="user_login" id="user_login" value="" placeholder="<?= __('Введите e-mail', 'bmr') ?>" autocomplete="off">
            <?php do_action( 'lostpassword_form' ); ?>
            <input type="hidden" name="redirect_to" value="<?php echo $_SERVER['REQUEST_URI']; ?>" />
            <button type="submit" name="submit" class="btn-submit-form"><?= __('Получить новый пароль', 'bmr') ?></button>
        </form>
        <div class="user-login-registration">
            <a href="#" class="login open-login-popup"><?= __('Вход на сайт', 'bmr') ?></a>
            <?php if (get_option('users_can_register')) { ?>
                | <a id="popup-reg-link" href="#" class="registration"><?= __('Регистрация', 'bmr') ?></a>
            <?php } ?>
        </div>
    </div>
</div>