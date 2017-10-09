<?php
namespace Bmr\Auth;

class Helper
{
    public static function validatePassword($pass)
    {
        $lenExp   = '#^(\S{6,})$#';
//        $lowerExp = '#(?=\S*[a-z])#';
//        $upperExp = '#(?=\S*[A-Z])#';
//        $numExp   = '#(?=\S*[\d])#';

        $lenExp   = preg_match_all($lenExp, $pass);
//        $lowerExp = preg_match_all($lowerExp, $pass);
//        $upperExp = preg_match_all($upperExp, $pass);
//        $numExp   = preg_match_all($numExp, $pass);

        if ($lenExp === false || $lenExp === 0) {
            return __('Пароль должен быть длиной минимум 6 символов!', 'bmr');
        }/* elseif ($lowerExp === false || $lowerExp === 0) {
            return __('Пароль должен содержать символы в нижнем регистре', 'bmr');
        } elseif ($upperExp === false || $upperExp === 0) {
            return __('Пароль должен содержать символы в верхнем регистре!', 'bmr');
        } elseif ($numExp === false || $numExp === 0) {
            return __('Пароль должен содержать хотя бы одну цифру!', 'bmr');
        }*/ else {
            return true;
        }
    }

    public static function mail($to, $subject, $message, $headers = '')
    {
        remove_all_filters('wp_mail_from');
        remove_all_filters('wp_mail_from_name');

        $sitename = get_bloginfo('name');
        $email    = get_bloginfo('admin_email');
        $from     = !empty($from) ? $from : "$sitename <$email>";

        $headers[] = 'From: ' . $from;

        return @wp_mail($to, $subject, $message, $headers, array());
    }

    public static function getActionInUnderscore($string)
    {
        return str_replace('_action', '', strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string)));
    }

    public static function getAvatarText($id)
    {
        $user = get_userdata((int) $id);
        if (!$user) {
            return '';
        }
        $res = mb_substr($user->last_name, 0, 1);
        return $res ? (mb_substr($user->first_name, 0, 1) . $res) : mb_substr($user->data->display_name, 0, 2);
    }

    public static function getAvatarUrl($id_or_email, $size = '96')
    {
        $avatar = get_avatar($id_or_email, $size);
        preg_match("/src=['\"](.*?)['\"]/i", $avatar, $matches);
        return $matches[1];
    }

    public static function isDefaultAvatar($id, $avaURI = '')
    {
        $user    = get_userdata((int)$id);
        $email   = $user ? $user->user_email : '';
        $hashkey = md5(strtolower(trim($email)));
        $uri     = 'http://www.gravatar.com/avatar/' . $hashkey . '?d=404';

        $data = wp_cache_get($hashkey);
        if (false === $data) {
            $response = wp_remote_head($uri);
            $data     = is_wp_error($response) ? 0 : $response['response']['code'];
            wp_cache_set($hashkey, $data);
        }

        return $data == 200 ? false : strpos($avaURI, 'gravatar.com/avatar') !== false;
    }

    public static function getCurrentUrl()
    {
        global $wp;
        return home_url(add_query_arg(array(), $wp->request));
    }

    public static function getRedirectUrl()
    {
        return !empty($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : self::getCurrentUrl();
    }
}