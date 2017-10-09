<div class="bmr-auth-form-container">
    <div class="bmr-auth-form-block">
        <h2><?php _e('Требуется авторизация!', 'bmr') ?></h2>
        <div class="bmr-auth-form">
            <form name="loginform" action="<?php echo site_url() . '/wp-login.php' ?>" method="post" class="user-login-item">

                <label for="user_login"><?php _e('E-Mail', 'bmr') ?></label>
                <input name="log" id="user_login_2" type="login" placeholder="Введите e-mail" required="">
                <label for="id_password"><?php _e('пароль', 'bmr') ?></label>
                <input name="pwd" id="id_password_2" type="password" placeholder="Введите пароль" required="">
                <div class="user-login-social form-row">
                    <span class="wp-social-login-connect-with-custom"><?php _e('Зайти с помощью', 'bmr') ?></span>
                    <?php do_action('wordpress_social_login'); ?>
                </div>
                <div class="checkbox form-row">
                    <input name="rememberme" id="remember_2" type="checkbox">
                    <label for="remember_2">
                        <?php _e('Запомнить меня', 'bmr') ?>
                    </label>
                </div>
                <input type="hidden" name="redirect_to" value="<?php echo $_SERVER['REQUEST_URI']; ?>"/>
                <button type="submit" name="submit" class="btn-submit-form"><?php _e('Войти', 'bmr') ?></button>
            </form>
            <div class="user-login-registration">
                <?php if (get_option('users_can_register')) { ?>
                <a href="" id="register-popup-link" class="registration"><?php _e('Регистрация', 'bmr') ?></a> |
                <?php } ?>
                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="password-recovery"><?php _e( 'Lost your password?' ); ?></a>
            </div>

        </div>
    </div>
</div>


