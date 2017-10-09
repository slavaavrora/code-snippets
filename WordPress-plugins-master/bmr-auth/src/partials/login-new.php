<div class="mfp-hide popupform-container" id="auth-form">
    <div class="popupform-header">
        <h2 class="popupform-title"><?= __('Вход на сайт', 'bmr') ?></h2>
    </div>
    <div class="bmr-loading-spinner"></div>
    <form id="loginform" class="popupform" action="<?= esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>" method="post">
        <p class="popupform-summary"></p>
        <div class="popupform-row">
            <label class="popupform-label" for="user_login"><?= __('E-Mail', 'bmr') ?></label>
            <input name="log" id="user_login" type="email" placeholder="<?= __('Введите e-mail', 'bmr') ?>">
        </div>
        <div class="popupform-row">
            <label class="popupform-label" for="id_password"><?= __('Пароль', 'bmr') ?></label>
            <input name="pwd" id="id_password" type="password" placeholder="<?= __('Введите пароль', 'bmr') ?>">
        </div>
        <?php do_action( 'login_form' ); ?>
        <div class="popupform-row">
            <span class="popupform-socials-header"><?= __('Зайти с помощью', 'bmr') ?></span>
            <?php do_action( 'wordpress_social_login' ); ?>
        </div>
        <div class="popupform-row">
            <input name="rememberme" id="remember" type="checkbox">
            <label for="remember">
                <?= __('Запомнить меня', 'bmr') ?>
            </label>
        </div>
        <div class="popupform-row">
            <input type="hidden" name="redirect_to" value="<?php echo $_SERVER['REQUEST_URI']; ?>" />
            <button type="submit" name="submit" class="popupform-submit"><?= __('Войти', 'bmr') ?></button>
        </div>
    </form>
    <div class="popupform-links">
        <?php if (get_option('users_can_register')) { ?>
            <a id="popup-reg-link" href="#" class="registration"><?= __('Регистрация', 'bmr') ?></a> |
        <?php } ?>
        <a href="<?php echo wp_lostpassword_url() ?>" class="password-recovery"><?= __('Забыли пароль?', 'bmr') ?></a>
    </div>
</div>
