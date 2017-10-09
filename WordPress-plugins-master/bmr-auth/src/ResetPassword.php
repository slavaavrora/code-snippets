<?php
namespace Bmr\Auth;

class ResetPassword
{
    /**
     * @var string
     */
    protected $rpLogin;
    /**
     * @var string
     */
    protected $rpKey;

    /**
     * @var string
     */
    protected $user;

    public function __construct()
    {
        $this->rpLogin = $this->rpKey = $this->user = null;
        add_action('init', [$this, 'resetPasswordHandler']);
    }

    public function resetPasswordHandler()
    {
        add_action('wp_footer', [$this, 'renderLostPasswordForm'], -1);

        if (isset($_GET['action']) && ($_GET['action'] == 'rp')) {
            if (isset($_GET['key']) && isset($_GET['email'])) {
                $this->rpLogin = wp_unslash($_GET['email']);
                $this->rpKey = wp_unslash($_GET['key']);

                $user = get_user_by('email', $this->rpLogin);

                if ($user) {
                    $this->user = check_password_reset_key($this->rpKey, $user->user_login);
                }
            } else {
                $this->user = false;
            }
            if ($this->user && !is_wp_error($this->user)) {
//                wp_enqueue_script('utils');
//                wp_enqueue_script('user-profile');
                add_action('wp_footer', [$this, 'renderResetPasswordForm'], -1);
            }
        }
    }

    public function userResetPassAction()
    {
        $response     = array();
        $success      = false;

        if (isset($_POST['_auth_nonce']) && wp_verify_nonce($_POST['_auth_nonce'], __FUNCTION__)) {

            $userLogin = isset($_POST['user_login']) ? $_POST['user_login'] : '';
            $userPass1 = isset($_POST['pass1']) ? $_POST['pass1'] : '';
            $userPass2 = isset($_POST['pass2']) ? $_POST['pass2'] : '';
            $rpKey = isset($_POST['rp_key']) ? $_POST['rp_key'] : '';
            $redirectTo = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : '';

            $user = get_user_by('email', $userLogin);
            $this->user = check_password_reset_key($rpKey, $user->user_login);
            $errors = new \WP_Error();

            if (empty($userPass1)) {
                $errors->add('pass1', 'Поле не заполнено!');
            }
            if (empty($userPass2)) {
                $errors->add('pass2', 'Поле не заполнено!');
            } elseif ($userPass1 != $userPass2) {
                $errors->add('pass2', 'Пароли не совпадают!');
            } elseif  (!$this->user || is_wp_error($this->user)) {
                if ($this->user && $this->user->get_error_code() === 'expired_key') {
                    $errors->add('summary', 'Извините, срок действия ключа истёк. Пожалуйста, попробуйте ещё раз.');
                } else {
                    $errors->add('summary', 'Извините, этот ключ неверен.');
                }
            }
            if (1 > count($errors->get_error_messages())) {
                reset_password($this->user, $userPass1);

                //login
                wp_signon([
                    'user_login' => $this->user->user_login,
                    'user_password' => $userPass1
                ]);

                $ava = Helper::getAvatarUrl($user->ID);
                if (!Helper::isDefaultAvatar($user->ID, $ava)) {
                    $response['data']['ava'] = $ava;
                } else {
                    $response['data']['display_name'] = Helper::getAvatarText($user->ID);
                }
                $response['data']['logout_link'] = wp_logout_url($redirectTo);
                $success = true;
            } else {
                $response['errors'] = $errors->errors;
            }
        } else {
            $response['errors']['summary'][] = 'Ошибка безопасности.';
        }
        $response['success'] = $success;
        exit(json_encode($response));
    }

    /**
     * Reset password form
     */
    public function renderResetPasswordForm()
    {
        include_once(BMR_AUTH_PARTIALS . '/reset-pass.php');
    }

    public function userRecoverPassAction()
    {
        $response     = array();
        $success      = false;

        if (isset($_POST['_auth_nonce']) && wp_verify_nonce($_POST['_auth_nonce'], __FUNCTION__)) {
            $errors = $this->retrieve_password();

            if (is_wp_error($errors)) {
                $response['errors'] = $errors->errors;
            } else {
                $success = true;
            }
        }
        $response['success'] = $success;
        exit(json_encode($response));
    }

    /**
     * Lost password form
     */
    public function renderLostPasswordForm()
    {
        include_once(BMR_AUTH_PARTIALS . '/lost-pass.php');
    }

    public function retrieve_password() {
        global $wpdb, $wp_hasher;

        $errors = new \WP_Error();
        $user_data = [];

        if (empty( $_POST['user_login'])) {
            $errors->add('user_login', __('Введите e-mail'));
        } elseif (strpos($_POST['user_login'], '@')) {
            $user_data = get_user_by('email', trim($_POST['user_login']));
            if (empty($user_data)) {
                $errors->add('user_login', __('Пользователей с таким адресом e-mail не зарегистрировано'));
            }
        } else {
            $errors->add('user_login', __('Вы должны ввести правильный e-mail адрес'));
        }

        do_action( 'lostpassword_post' );
        if ($errors->get_error_code()) {
            return $errors;
        }

        $user_email = $user_data->user_email;
        /**
         * Fires before a new password is retrieved.
         */
        do_action('retreive_password', $user_email);
        /**
         * Fires before a new password is retrieved.
         */
        do_action('retrieve_password', $user_email);
        /**
         * Filter whether to allow a password to be reset.
         */
        $allow = apply_filters('allow_password_reset', true, $user_data->ID);

        if ( ! $allow ) {
            return new WP_Error( 'no_password_reset', __('Password reset is not allowed for this user') );
        } elseif ( is_wp_error( $allow ) ) {
            return $allow;
        }

        // Generate something random for a password reset key.
        $key = wp_generate_password(20, false);

        /**
         * Fires when a password reset key is generated.
         */
        do_action( 'retrieve_password_key', $user_email, $key );

        // Now insert the key, hashed, into the DB.
        if ( empty( $wp_hasher ) ) {
            require_once ABSPATH . WPINC . '/class-phpass.php';
            $wp_hasher = new \PasswordHash( 8, true );
        }
        $hashed = time() . ':' . $wp_hasher->HashPassword( $key );
        $wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_email' => $user_email ) );

        $message = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";
        $message .= network_home_url( '/' ) . "\r\n\r\n";
        $message .= sprintf(__('E-Mail: %s'), $user_email) . "\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
        $message .= network_site_url("wp-login.php?action=rp&key=$key&email=" . rawurlencode($user_email), 'login') . "\r\n";

        if (is_multisite()) {
            $blogname = $GLOBALS['current_site']->site_name;
        } else  {
            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        }

        $title = sprintf( __('[%s] Password Reset'), $blogname );
        /**
         * Filter the subject of the password reset email.
         */
        $title = apply_filters( 'retrieve_password_title', $title );

        /**
         * Filter the message body of the password reset mail.
         */
        $message = apply_filters( 'retrieve_password_message', $message, $key, $user_email, $user_data );

        if ($message && !Helper::mail($user_email, wp_specialchars_decode($title), $message)) {
            wp_die(__('The e-mail could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.'));
        }
        return true;
    }

}