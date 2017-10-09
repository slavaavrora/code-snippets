<?php


namespace Bmr\Auth;


class UrlFilter {

    public function __construct()
    {
        add_filter('register_url', [$this, 'changeRegisterUrl'], 10, 1);
        add_filter('lostpassword_url', [$this, 'changeResetPasswordUrl'], 10, 1);
        add_filter('login_url', [$this, 'changeLoginUrl'], 10, 2);

        add_filter('network_site_url', [$this, 'changeNetworkSiteUrlForPassRecovery'], 30);
        add_filter('network_home_url', [$this, 'changeNetworkHomeUrlForPassRecovery'], 10, 3);
    }

    /**
     * Register URL Filter
     *
     * @return string
     */
    public function changeRegisterUrl($url) {
        return add_query_arg('action', 'register', site_url());
    }

    /**
     * Reset Password URL Filter
     *
     * @return string
     */
    public function changeResetPasswordUrl($url) {
        $args = array( 'action' => 'lostpassword' );
        if ( !empty($redirect) ) {
            $args['redirect_to'] = $redirect;
        }
        $lostpassword_url = add_query_arg($args, site_url());
        return $lostpassword_url;
    }

    /**
     * Login URL Filter
     *
     * @return string
     */
    public function changeLoginUrl($url, $redirect)
    {
        $args = array( 'auth' => '1' );

        if (!empty($redirect)) {
            $args['redirect_to'] = $redirect;
        }
        $login_url = add_query_arg($args, site_url());
        return $login_url;
    }


    //.TODO: rework
    public function changeNetworkSiteUrlForPassRecovery($url, $path = '', $scheme = null)
    {
        if (strpos($url, 'rp') !== false || strpos($url, 'resetpass') !== false) {
            $url = preg_replace('/wp-login\.php/', '', $url);

        } elseif (strpos($url, 'wp-login') !== false) {
            preg_match('/(wp-login.*)/', $url, $matches);

            if (isset($matches[1])) {
                $url = site_url($matches[1]);
            }
        } elseif (strpos($url, 'wp-signup') !== false) {
            $url = add_query_arg('action', 'register', site_url());
        }
        return $url;
    }

    public function changeNetworkHomeUrlForPassRecovery($url, $path = '', $scheme = null)
    {
        if (empty($path)) {
            $url = home_url();
        }
        return $url;
    }
}