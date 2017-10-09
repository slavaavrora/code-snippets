<?php use Bmr\Auth\Helper; ?>
<div class="user-login-block mfp-hide" id="auth-form">
    <div class="background">
        <div class="user-login-header">
            <h3><?= __('Вход на сайт', 'bmr') ?></h3>
        </div>
        <div class="bmr-loading-spinner on-wp-login-form"></div>
        <form name="loginform" action="<?= esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>" method="post" id="loginform" class="user-login-item">
            <p class="form-info summary"></p>
            <label for="user_login"><?= __('E-Mail', 'bmr') ?></label>
            <input name="log" id="user_login" type="text" placeholder="<?= __('Введите e-mail', 'bmr') ?>">

            <label for="id_password"><?= __('Пароль', 'bmr') ?></label>
            <input name="pwd" id="id_password" type="password" placeholder="<?= __('Введите пароль', 'bmr') ?>">

            <?php do_action( 'login_form' ); ?>

            <div class="user-login-social">
                <span class="wp-social-login-connect-with-custom"><?= __('Зайти с помощью', 'bmr') ?></span>
                <?php do_action( 'wordpress_social_login' ); ?>
            </div>

            <div class="form-item-checkbox">
                <input name="rememberme" id="remember" type="checkbox" class="login-checkbox-inp">
                <label for="remember" class="login-checkbox">
                    <?= __('Запомнить меня', 'bmr') ?>
                </label>
            </div>

            <input type="hidden" name="redirect_to" value="<?= Helper::getRedirectUrl(); ?>" />
            <button type="submit" name="submit" class="btn-submit-form"><?= __('Войти', 'bmr') ?></button>
        </form>
        <div class="user-login-registration">
            <?php if (get_option('users_can_register')) { ?>
                <a id="popup-reg-link" href="#" class="registration"><?= __('Регистрация', 'bmr') ?></a> |
            <?php } ?>
            <a href="<?php echo wp_lostpassword_url() ?>" class="password-recovery"><?= __('Забыли пароль?', 'bmr') ?></a>
        </div>
    </div>
</div>