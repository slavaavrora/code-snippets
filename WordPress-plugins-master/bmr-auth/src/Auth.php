<?php
namespace Bmr\Auth;

class Auth
{

    public function __construct()
    {
        if (!is_user_logged_in()) {
            add_action('wp_footer', [$this, 'renderRegisterForm'], -1);
            add_action('wp_footer', [$this, 'renderAuthForm'], -1);
        }
        add_action('wp_authenticate', [$this, 'authenticateByEmail'], 10, 1);
    }

    /**
     * Hook to allow E-Mail authetification
     *
     * @param $username
     */
    public function authenticateByEmail(&$username)
    {
        if (!empty($username) && is_email($username)) {
            $user = get_user_by('email', $username);

            if ($user) {
                $username = $user->user_login;
            }
        }
    }


    public function userAuthAction()
    {
        $response      = array();
        $success       = false;
        $secure_cookie = '';

        if (isset($_COOKIE['bmr-user-status'])) {
            $response = [
                'errors' => [
                    'summary' => [__('Произошла неизвестная ошибка, попробуйте еще раз позже.', 'bmr')]
                ],
                'success' => $success
            ];
            exit(json_encode($response));
        }

        if (isset($_POST['_auth_nonce']) && wp_verify_nonce($_POST['_auth_nonce'], __FUNCTION__)) {

            // If the user wants ssl but the session is not ssl, force a secure cookie.
            if (!empty($_POST['log']) && !force_ssl_admin()) {
                $user_email = sanitize_user($_POST['log']);
                if ($user = get_user_by('email', $user_email)) {
                    if (get_user_option('use_ssl', $user->ID)) {
                        $secure_cookie = true;
                        force_ssl_admin(true);
                    }
                }
            }

            if (isset($_REQUEST['redirect_to'])) {
                $redirect_to = $_REQUEST['redirect_to'];
                // Redirect to https if user wants ssl
                if ($secure_cookie && false !== strpos($redirect_to, 'wp-admin')) {
                    $redirect_to = preg_replace('|^http://|', 'https://', $redirect_to);
                }
            } else {
                $redirect_to = admin_url();
            }

            $user   = wp_signon('', $secure_cookie);

            $requested_redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '';
            /**
             * Filter the login redirect URL.
             */
            $redirect_to = apply_filters('login_redirect', $redirect_to, $requested_redirect_to, $user);

            // logged in successfully.
            if (!is_wp_error($user)) {

                if ((empty($redirect_to) || $redirect_to == 'wp-admin/' || $redirect_to == admin_url())) {
                    // If the user doesn't belong to a blog, send them to user admin. If the user can't edit posts, send them to their profile.
                    if (is_multisite() && !get_active_blog_for_user($user->ID) && !is_super_admin($user->ID)) {
                        $redirect_to = user_admin_url();
                    } elseif (is_multisite() && !$user->has_cap('read')) {
                        $redirect_to = get_dashboard_url($user->ID);
                    } elseif (!$user->has_cap('edit_posts')) {
                        $redirect_to = admin_url('profile.php');
                    }
                    $redirect_to = remove_query_arg('auth', $redirect_to);
                }
                $success = true;
                $response['redirect_to'] = $redirect_to;

            } else {
                $data = $this->filterAuthErrors($user);
                $response['errors'] = $data->errors;;
            }
        } else {
            $response['errors']['summary'] = array(__('Ошибка безопасности.', 'bmr'));
        }
        $response['success'] = $success;
        exit(json_encode($response));
    }

    public function filterAuthErrors($data)
    {
        $errorsAssoc = [
            'empty_username'     => ['user_login', __('Вы не ввели e-mail.', 'bmr')],
            'empty_password'     => ['id_password', __('Вы не ввели пароль.', 'bmr')],
            'invalid_username'   => ['user_login', __('Неправильный e-mail.', 'bmr')],
            'incorrect_password' => ['id_password', __('Неправильный пароль.', 'bmr')]
        ];
        $errors = new \WP_Error();

        if (empty($data->errors)) {
            $errors->add('user_login', __('Вы не ввели e-mail.', 'bmr'));
            $errors->add('id_password', __('Вы не ввели пароль.', 'bmr'));
        } else {
            foreach ($data->errors as $error_key => $error) {
                if (array_key_exists($error_key, $errorsAssoc)) {
                    $errors->add($errorsAssoc[$error_key][0], $errorsAssoc[$error_key][1]);
                }
            }
        }
        return $errors;
    }

    public function userRegisterAction()
    {
        $response     = array();
        $success      = false;

        if (isset($_POST['_auth_nonce']) && wp_verify_nonce($_POST['_auth_nonce'], __FUNCTION__) && isset($_POST['conditions'])) {
            $userName     = isset($_POST['user_name']) ? $_POST['user_name'] : null;
            $userSurname  = isset($_POST['user_surname']) ? $_POST['user_surname'] : null;
            $userEmail    = isset($_POST['user_email']) ? $_POST['user_email'] : null;
            $userPassword = isset($_POST['user_password']) ? $_POST['user_password'] : null;
            $referer = isset($_POST['referer']) ? $_POST['referer'] : '';

            $errors = new \WP_Error();

            if (isset($_COOKIE['bmr-user-status'])) {
                $errors->add('summary', __('Произошла неизвестная ошибка, попробуйте еще раз позже.', 'bmr'));
            }

            // name check
            if (empty($userName)) {
                $errors->add('user_name', __('Пустое поле!', 'bmr'));
            }
            // surname check
            if (empty($userName)) {
                $errors->add('user_surname', __('Пустое поле!', 'bmr'));
            }
            // password check
            if (empty($userPassword)) {
                $errors->add('user_password', __('Пустое поле!', 'bmr'));
            } elseif (($wrongPass = Helper::validatePassword($userPassword)) !== true) {
                $errors->add('user_password', $wrongPass);
            }

            // email check
            if (empty($userEmail)) {
                $errors->add('user_email', __('Пустое поле!', 'bmr'));
            } elseif (!is_email($userEmail)) {
                $errors->add('user_email', __('Неправильный E-Mail!', 'bmr'));
            } elseif (email_exists($userEmail)) {
                $errors->add('user_email', __('Такой E-Mail уже используется!', 'bmr'));
            }

            if (1 > count($errors->get_error_messages())) {

                $userLogin = $this->generateUsernameByEmail($userEmail);
                $userdata = array(
                    'user_login' => $userLogin,
                    'first_name' => $userName,
                    'last_name'  => $userSurname,
                    'user_email' => $userEmail,
                    'user_pass'  => $userPassword,
                );
                $userId = wp_insert_user($userdata);

                if (!is_wp_error($userId)) {

                    //send notification
                    $this->sendUserNotification($userId, $userPassword);

                    //login
                    wp_signon([
                        'user_login' => $userLogin,
                        'user_password' => $userPassword
                    ]);

                    $response['data']['redirect_to'] = $referer;
                    $success = true;
                } else {
                    $response['errors']['summary'] = $userId->get_error_message();
                }
            } else {
                $response['errors'] = $errors->errors;
            }
        } else {
            $response['errors']['summary'] = array(__('Ошибка безопасности.', 'bmr'));
        }
        $response['success'] = $success;
        exit(json_encode($response));
    }

    public function renderRegisterForm()
    {
        global $privacyPolicy, $termsOfUse;
        include_once(BMR_AUTH_PARTIALS . '/register.php');
    }

    public function renderAuthForm()
    {
        include_once(BMR_AUTH_PARTIALS . '/login.php');
    }

    public function generateUsernameByEmail($email)
    {
        $userLogin = strstr($email, '@', true);
        /** @var \wpdb $wpdb */
        global $wpdb;
        $userExists = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM $wpdb->users WHERE user_login = %s",
            $userLogin
        ));
        if ($userExists) {
            $userLogin .= uniqid();
        }
        return $userLogin;
    }

    public function sendUserNotification($user_id, $plaintext_pass = '')
    {
        $user = get_userdata( $user_id );
        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

        $message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
        $message .= sprintf(__('E-mail: %s'), $user->user_email) . "\r\n";
        Helper::mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);

        if (empty($plaintext_pass)) {
            return;
        }
        $message = sprintf(__('Password: %s'), $plaintext_pass) . "\r\n";
        $message .= wp_login_url() . "\r\n";
        Helper::mail($user->user_email, sprintf(__('[%s] Регистрационные данные', 'bmr'), $blogname), $message);
    }
}