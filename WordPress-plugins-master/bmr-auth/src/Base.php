<?php
namespace Bmr\Auth;

class Base
{
    /**
     * @var UrlFilter
     */
    private $urlFilter;

    /**
     * @var Auth
     */
    private $authInst;

    /**
     * @var ResetPassword
     */
    private $resetPassInst;

    /**
     * @var array
     */
    private $actions;

    public function __construct()
    {
        $this->urlFilter     = new UrlFilter();
        $this->authInst      = new Auth();
        $this->resetPassInst = new ResetPassword();
        $this->actions       = [];
    }

    public function init()
    {
        $this->registerAjaxAction('register', $this->authInst, 'userRegisterAction');
        $this->registerAjaxAction('auth', $this->authInst, 'userAuthAction');
        $this->registerAjaxAction('reset', $this->resetPassInst, 'userResetPassAction');
        $this->registerAjaxAction('recover', $this->resetPassInst, 'userRecoverPassAction');

        add_action('wp_enqueue_scripts', [$this, 'loadStyles'], 11);
        add_action('wp_enqueue_scripts', [$this, 'loadScripts'], 11);
    }

    public function loadStyles()
    {
        wp_enqueue_style(
            'bmr-auth',
            plugins_url('/assets/css/bmr-auth.css', __FILE__),
            array(),
            BMR_AUTH_VERSION
        );
    }

    public function loadScripts()
    {
        wp_enqueue_script(
            'magnific-popup',
            plugins_url('/assets/components/magnific-popup/jquery.magnific-popup.min.js', __FILE__),
            array('jquery'),
            BMR_AUTH_VERSION,
            true
        );
        wp_enqueue_script(
            'bootstrap-tooltip',
            plugins_url('/assets/js/bs3-tooltip.js', __FILE__),
            array('jquery'),
            BMR_AUTH_VERSION,
            true
        );
        wp_enqueue_script(
            'bootstrap-popover',
            plugins_url('/assets/js/bs3-popover.js', __FILE__),
            array('bootstrap-tooltip'),
            BMR_AUTH_VERSION,
            true
        );
        wp_enqueue_script(
            'bmr-auth',
            plugins_url('/assets/js/bmr-auth.js', __FILE__),
            array('magnific-popup', 'bootstrap-popover', 'bootstrap-tooltip'),
            BMR_AUTH_VERSION,
            true
        );
        wp_localize_script(
            'bmr-auth',
            'bmrAuth',
            [
                'action'     => $this->actions,
                'ajaxurl'    => admin_url('admin-ajax.php'),
                'profileurl' => admin_url('profile.php'),
                'msgTexts'   => [
                    'success' => [
                        'h3'                  => __('Регистрация', 'bmr'),
                        'registerMessage'     => __('Вы успешно зарегистрировались<br>и можете зайти в <a href="{uri}">свой аккаунт!</a>', 'bmr'),
                        'registerMessageInfo' => __('На указанный Вами электронный адрес<br>выслано письмо с учетными данными', 'bmr'),
                        'registerMessageThx'  => __('Благодарим за регистрацию!', 'bmr'),
                    ],
                    'error' => [
                        'h3'                   => __('Регистрация', 'bmr'),
                        'registerMessage'      => __('Что-то пошло не так...', 'bmr'),
                        'registerMessageError' => __('[Код ошибки: {code}]', 'bmr'),
                        'registerMessageInfo'  => __('Попробуйте еще раз чуть позже. Или свяжитесь<br>со службой поддержки:', 'bmr'),
                        'registerMessageThx'   => __('Благодарим за понимание!', 'bmr'),
                    ],
                    'confirm' => [
                        'h3'                  => __('Регистрация', 'bmr'),
                        'registerMessage'     => __('Вы успешно подтвердили Ваш e-mail.', 'bmr'),
                        'registerMessageInfo' => __('Добро пожаловать на сайт!', 'bmr'),
                        'registerMessageThx'  => __('Благодарим за подтверждение!', 'bmr'),
                    ],
                    'passreset' => [
                        'h3'                 => __('Новый пароль', 'bmr'),
                        'registerMessage'    => __('На указанный Вами электронный адрес<br>выслано письмо с новым паролем.', 'bmr'),
                        'registerMessageThx' => __('Пароль отправлен', 'bmr'),
                    ]
                ]
            ]
        );

    }

    public function registerAjaxAction($slug, $instance, $action)
    {
        $jsAction = Helper::getActionInUnderscore($action);

        $this->actions[$slug] = [
            'action' => $jsAction,
            'nonce'  => wp_create_nonce($action)
        ];

        add_action("wp_ajax_$jsAction", [$instance, $action]);
        add_action("wp_ajax_nopriv_$jsAction", [$instance, $action]);
    }
    
    public function activate() {}

    public function deactivate() {}
}