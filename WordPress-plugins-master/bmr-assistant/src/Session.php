<?php


namespace Bmr\Assistant;


class Session {

    public static function init()
    {
        add_action('wp_logout', [__CLASS__, 'close']);
        add_action('wp_login', [__CLASS__, 'close']);
    }

    public static function set($name, $value)
    {
        self::start();
        $_SESSION[$name] = $value;
    }

    public static function get($name, $delete = false) {
        self::start();
        $value = isset($_SESSION[$name]) ? $_SESSION[$name] : false;

        if ($delete) {
            unset($_SESSION[$name]);
        }
        return $value;
    }

    public static function start()
    {
        if(!session_id()) {
            session_start();
        }
    }

    public static function close()
    {
        self::start();
        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}