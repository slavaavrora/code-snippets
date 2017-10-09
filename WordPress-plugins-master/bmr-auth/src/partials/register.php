<?php use Bmr\Auth\Helper; ?>
<div class="user-register-row">
    <div class="user-register-block mfp-hide" id="reg-form">
        <div class="background">
            <div class="user-register-header">
                <h3><?= __('Регистрация', 'bmr') ?></h3>
            </div>
            <div class="reg-form-container" >
                <div class="bmr-loading-spinner on-wp-login-form"></div>
                <form name="registerform" action="" method="post" id="registerform" class="user-register-item">
                    <p class="form-info summary"></p>
                    <div class="user-login-social">
                        <span class="wp-social-login-connect-with-custom"><?= __('Регистрация с помощью', 'bmr') ?></span>
                        <?php do_action( 'wordpress_social_login' ); ?>
                        <span class="user-login-or"><?= __('или', 'bmr') ?></span>
                    </div>
                    <label for="user_name"><?= __('Имя', 'bmr') ?></label>
                    <input name="user_name" id="user_name" type="text" placeholder="<?= __('Введите имя', 'bmr') ?>" value="">
                    <label for="user_surname"><?= __('Фамилия', 'bmr') ?></label>
                    <input name="user_surname" id="user_surname" type="text" placeholder="<?= __('Введите фамилию', 'bmr') ?>" value="">
                    <label for="user_email"><?= __('E-Mail', 'bmr') ?></label>
                    <input name="user_email" id="user_email" type="email" placeholder="<?= __('Введите e-mail', 'bmr') ?>" value="">
                    <label for="user_password"><?= __('Пароль', 'bmr') ?></label>
                    <input name="user_password" id="user_password" type="password" placeholder="<?= __('Введите пароль', 'bmr') ?>" value="">
                    <input name="referer" type="hidden" value="<?= Helper::getRedirectUrl(); ?>" />
                    <div class="form-item-checkbox">
                        <input name="conditions" id="conditions" type="checkbox" class="login-checkbox-inp">
                        <label for="conditions" class="login-checkbox conditions-label">
                            <?php
                            printf(__('Я согласен с <a href="%s" target="_blank">Условиями использования сайта</a> и <a href="%s" target="_blank">Политикой конфиденциальности</a>', 'bmr'), $termsOfUse, $privacyPolicy);
                            ?>
                        </label>
                    </div>
                    <button type="submit" name="submit" class="btn-submit-form"><?= __('Зарегистрироваться', 'bmr') ?></button>
                </form>
                <div class="user-login-registration">
                    <a href="" class="login open-login-popup"><?= __('Вход на сайт', 'bmr') ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

