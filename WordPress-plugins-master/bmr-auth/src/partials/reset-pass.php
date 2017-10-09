<?php use Bmr\Auth\Helper; ?>
<div class="user-login-block mfp-hide reset-pass-form" id="reset-form">
    <div class="background">
        <div class="user-login-header">
            <h3><?= __('Новый пароль', 'bmr') ?></h3>
        </div>
        <div class="bmr-loading-spinner on-wp-login-form"></div>
        <form name="resetpassform" action="<?= esc_url(network_site_url('wp-login.php?action=resetpass', 'login_post')); ?>" method="post" id="resetpassform" class="user-login-item" autocomplete="off">
            <p class="form-info summary"></p>
            <input type="hidden" name="user_login" id="user_login" value="<?= esc_attr($this->rpLogin) ?>" autocomplete="off" />
            <label for="pass1"><?= __('Новый пароль', 'bmr') ?></label>
            <input name="pass1" id="pass1" type="password" placeholder="<?= __('Новый пароль', 'bmr') ?>" value="" autocomplete="off">
            <label for="pass2"><?= __('Повторите пароль', 'bmr') ?></label>
            <input name="pass2" id="pass2" type="password" placeholder="<?= __('Повторите пароль', 'bmr') ?>" value="" autocomplete="off">
            <?php /*do_action('resetpass_form', $this->user);*/ ?>
            <input type="hidden" name="rp_key" value="<?= esc_attr($this->rpKey); ?>" />
            <input type="hidden" name="redirect_to" value="<?= esc_url(remove_query_arg(array('action', 'key', 'email', '_auth_nonce'))); ?>" />
            <button type="submit" name="submit" class="btn-submit-form"><?= __('Сменить пароль и войти', 'bmr') ?></button>
        </form>
    </div>
</div>