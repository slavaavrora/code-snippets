<?php

/**
 * Class BmrOptions
 */
class BmrOptions
{
    /**
     * @var string
     */
    private static $pageSlug = null;

    /**
     * @var string
     */
    private static $blacklistPageSlug = null;

    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'addPluginAdminMenu'));
    }

    public static function getDefaultSettings()
    {
        return array(
            'bmr_form_mail_subject'    => __('Жалоба отправлена', 'bmr'),
            'bmr_form_mail_tpl'        => __('Ваша жалоба была успешно отправлена и ожидает обработки.', 'bmr'),
            'bmr_comment_mail_subject' => __('Вам написали в обсуждении жалобы', 'bmr'),
            'bmr_comment_mail_tpl'     => __('Пользователь "%user%" написал вам сообщение.<br>Посмотреть <a href="%comment_url%">это</a> сообщение.', 'bmr'),
            'bmr_email'                => 'complaints@bookmakersrating.ru',
            'bmr_form_header_txt'      => '<p>' . __('Если вам кажется, что с вами поступили несправедливо и вы хотите пожаловаться на букмекерскую контору, мы советуем для начала обратиться в службу поддержки этой конторы и попытаться узнать в чем причина такого решения. После того, как вы убедитесь что далее с конторой не имеет смыла вести переговоры, заполните форму жалобы и постарайтесь как можно детальнее рассказать о вашем случае. Также, прикрепите к тексту вашей жалобы переписку со службой поддержки.', 'bmr') . '</p>',

            'bmr_form_footer_txt'      => __('<h4>Спасите наши уши!</h4><p>Мы понимаем, что вас, должно быть, сейчас распирает от эмоций, и они отнюдь не положительные, но мы все же настоятельно просим Вас воздержаться от мата. Среди наших модераторов есть и девушки, пожалуйста, не заставляйте их краснеть! Мы сможем добиться решения всех вопросов и без обсценной лексики!</p>', 'bmr'),
            'bmr_form_success_txt'     => __('Мы рассмотрим Вашу жалобу в ближайшее время, и сделаем все, что бы решить Вашу проблему.<br>Мы свяжемся с Вами по электронной почте.<br><br><span class="grey">Если у вас остались какие либо вопросы, пишите нам сюда: </span><a href="mailto:info@bookmakersrating.ru">info@bookmakersrating.ru</a><br>', 'bmr'),
            'bmr_archive_header_txt'   => __('<p>На этой странице «Рейтинг Букмекеров» публикует поступающие жалобы на букмекерские конторы. В целях безопасности из жалоб удаляется вся личная информация, а также информация, позволяющая идентифицировать жалующегося игрока.</p>Помимо самих жалоб, «Рейтинг Букмекеров» сообщает об исходе обращения по ним в букмекерские компании. Вы можете узнать, какие жалобы были удовлетворены, какие не подтвердились; какие БК отказываются идти на встречу своим игрокам.<p></p>', 'bmr')
        );
    }

    public static function setDefaultSettings()
    {
        $defaults = self::getDefaultSettings();
        update_option('bmr_settings', $defaults, false);
    }

    /**
     * Get/Set option in plugin settings
     *
     * @param string $name option name
     * @param string|int $value new option value
     * @return bool|string
     */
    public static function option($name, $value = null)
    {
        if (!is_string($name) || empty($name)) {
            return '';
        } else {
            $settings = get_option('bmr_settings', array());
            if (!array_key_exists($name, $settings)) {
                return '';
            }
            if (empty($value) && $value !== 0) {
                return $settings[$name];
            } else {
                $settings[$name] = is_numeric($value) ? $value : trim($value);
                return update_option('bmr_settings', $settings);
            }
        }
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     */
    public static function addPluginAdminMenu()
    {
        /** REGISTER SETTINGS FOR PLUGIN */
        register_setting('bmr-settings', 'bmr_settings', array(__CLASS__, 'sanitizeSettings'));

        self::$blacklistPageSlug = add_submenu_page(
            'edit.php?post_type=' . BmrConfig::POST_TYPE,
            __('Черный список E-Mail адресов', 'bmr'),
            __('Черный список', 'bmr'),
            'manage_options',
            'bmr-blacklist',
            array(__CLASS__, 'displayBlackList')
        );

        /** ADDING PLUGIN OPTIONS PAGE TO TOOLS MENU */
        self::$pageSlug = add_submenu_page(
            'edit.php?post_type=' . BmrConfig::POST_TYPE,
            __('Настройка системы жалоб', 'bmr'),
            __('Настройки', 'bmr'),
            'manage_options',
            'bmr-settings',
            array(__CLASS__, 'displayAdminPage')
        );

        /** PROCESING SECTIONS AND FIELDS ON OPTIONS PAGE */
        $settings = get_option('bmr_settings');

        /** SECTION 1 */
        add_settings_section('general', __('Общие', 'bmr'), '', 'bmr-settings');
        add_settings_field(
            'bmr_email',
            __('E-Mail:', 'bmr'),
            array(__CLASS__, 'inputText'),
            'bmr-settings',
            'general',
            array(
                'name'  => 'bmr_settings[bmr_email]',
                'value' => $settings['bmr_email'],
                'description' => __('E-Mail с которого отправлять письма','bmr')
            )
        );

        add_settings_field(
            'bmr_form_mail_subject',
            __('Тема письма <br>(Форма добавления):', 'bmr'),
            array(__CLASS__, 'inputText'),
            'bmr-settings',
            'general',
            array(
                'name'  => 'bmr_settings[bmr_form_mail_subject]',
                'value' => $settings['bmr_form_mail_subject'],
            )
        );
        add_settings_field(
            'bmr_form_mail_tpl',
            __('Шаблон письма <br>(Форма добавления):', 'bmr'),
            array(__CLASS__, 'inputTextArea'),
            'bmr-settings',
            'general',
            array(
                'name'  => 'bmr_settings[bmr_form_mail_tpl]',
                'value' => $settings['bmr_form_mail_tpl'],
                'description' => ''
            )
        );

        $desctiption = <<<EOF
        Можно использовать следующие шорткоды для вывода специальных полей:
        <br><br>
        <strong>%comment_url%</strong> : Ссылка на комментарий с ответом.
        <br>
        (<i>Пример:</i> http://new.bookmakersrating.ru/blog/complaints/zaderzhka-vy-platy-william-hill-7-noyabrya-2014#comment-19)
        <br>
        <strong>%user%</strong> : Пользователь который оставил ответ.
        <br>
        (<i>Пример:</i> Петр Петров)
EOF;

        add_settings_field(
            'bmr_comment_mail_subject',
            __('Тема письма <br>(Система комментариев):', 'bmr'),
            array(__CLASS__, 'inputText'),
            'bmr-settings',
            'general',
            array(
                'name'  => 'bmr_settings[bmr_comment_mail_subject]',
                'value' => $settings['bmr_comment_mail_subject'],
            )
        );
        add_settings_field(
            'bmr_comment_mail_tpl',
            __('Шаблон письма <br>(Система комментариев):', 'bmr'),
            array(__CLASS__, 'inputTextArea'),
            'bmr-settings',
            'general',
            array(
                'name'  => 'bmr_settings[bmr_comment_mail_tpl]',
                'value' => $settings['bmr_comment_mail_tpl'],
                'description' => $desctiption
            )
        );

        add_settings_section('form_text', __('Тексты на странице с формой отправки жалобы', 'bmr'), '', 'bmr-settings');
        add_settings_field(
            'bmr_form_header',
            __('Текст перед формой:', 'bmr'),
            array(__CLASS__, 'inputTextArea'),
            'bmr-settings',
            'form_text',
            array(
                'name'  => 'bmr_settings[bmr_form_header_txt]',
                'value' => $settings['bmr_form_header_txt'],
                'description' => ''
            )
        );
        add_settings_field(
            'bmr_form_footer',
            __('Текст после формы:', 'bmr'),
            array(__CLASS__, 'inputTextArea'),
            'bmr-settings',
            'form_text',
            array(
                'name'  => 'bmr_settings[bmr_form_footer_txt]',
                'value' => $settings['bmr_form_footer_txt'],
                'description' => ''
            )
        );
        add_settings_field(
            'bmr_form_success',
            __('Текст при успешно отправленной жалобе:', 'bmr'),
            array(__CLASS__, 'inputTextArea'),
            'bmr-settings',
            'form_text',
            array(
                'name'  => 'bmr_settings[bmr_form_success_txt]',
                'value' => $settings['bmr_form_success_txt'],
                'description' => ''
            )
        );
        add_settings_section('complaints_section', __('Жалобы', 'bmr'), '', 'bmr-settings');
        add_settings_field(
            'complaint_archive_desc',
            __('Описание на архивных страницах жалоб:', 'bmr'),
            array(__CLASS__, 'inputTextArea'),
            'bmr-settings',
            'complaints_section',
            array(
                'name'  => 'bmr_settings[bmr_archive_header_txt]',
                'value' => $settings['bmr_archive_header_txt'],
                'description' => ''
            )
        );
        add_settings_field(
            'complaint_kapper_archive_desc',
            __('Описание на архивных страницах капперских жалоб:', 'bmr'),
            array(__CLASS__, 'inputTextArea'),
            'bmr-settings',
            'complaints_section',
            array(
                'name'  => 'bmr_settings[bmr_archive_kapper_header_txt]',
                'value' => $settings['bmr_archive_kapper_header_txt'],
                'description' => ''
            )
        );

    }

    /**
     * @param $args
     */
    public static function inputText($args)
    {
        $name  = esc_attr($args['name']);
        $value = esc_attr($args['value']);
        $description = isset($args['description']) ? $args['description'] : '';

        echo "<input type='text' name='{$name}' value='{$value}' size='45' />";
        if (!empty($description)) {
            echo '<div style="margin-top: 10px;"><span class="description">' . $description . '</span></div>';
        }
    }

    /**
     * @param $args
     */
    public static function inputTextArea($args)
    {
        $name  = esc_attr($args['name']);
        $value = $args['value'];
        $description = isset($args['description']) ? $args['description'] : '';

        $settings = array(
            'teeny' => false,
            'media_buttons' => false,
            'textarea_rows' => 15,
            'textarea_name' => $name
        );
        wp_editor($value, 'wp_editor_' . uniqid(), $settings);
        if (!empty($description)) {
            echo '<div style="margin-top: 10px;"><span class="description">' . $description . '</span></div>';
        }
    }

    /**
     * @param $data
     * @return array
     */
    public static function sanitizeSettings($data)
    {
        $output = get_option('bmr_settings');

        if (filter_var($data['bmr_email'], FILTER_VALIDATE_EMAIL) && is_email($data['bmr_email'])
            && $data['bmr_email'] != $output['bmr_email']
        ) {
            $output['bmr_email'] = esc_attr($data['bmr_email']);
            add_settings_error(
                'bmr_settings',
                'email-update',
                __('Поле "E-Mail" обновлено.', 'bmr'),
                'updated'
            );
        } elseif ($data['bmr_email'] != $output['bmr_email']) {
            add_settings_error(
                'bmr_settings',
                'invalid-email',
                __('Вы ввели не верный E-Mail!', 'bmr')
            );
        }

        $output['bmr_form_mail_subject']    = isset($data['bmr_form_mail_subject']) ? $data['bmr_form_mail_subject'] : $output['bmr_form_mail_subject'];
        $output['bmr_form_mail_tpl']        = isset($data['bmr_form_mail_tpl']) ? $data['bmr_form_mail_tpl'] : $output['bmr_form_mail_tpl'];
        $output['bmr_comment_mail_subject'] = isset($data['bmr_comment_mail_subject']) ? $data['bmr_comment_mail_subject'] : $output['bmr_comment_mail_subject'];
        $output['bmr_comment_mail_tpl']     = isset($data['bmr_comment_mail_tpl']) ? $data['bmr_comment_mail_tpl'] : $output['bmr_comment_mail_tpl'];
        $output['bmr_form_header_txt']      = isset($data['bmr_form_header_txt']) ? $data['bmr_form_header_txt'] : $output['bmr_form_header_txt'];
        $output['bmr_form_footer_txt']      = isset($data['bmr_form_footer_txt']) ? $data['bmr_form_footer_txt'] : $output['bmr_form_footer_txt'];
        $output['bmr_form_success_txt']     = isset($data['bmr_form_success_txt']) ? $data['bmr_form_success_txt'] : $output['bmr_form_success_txt'];
        $output['bmr_archive_header_txt']   = isset($data['bmr_archive_header_txt']) ? $data['bmr_archive_header_txt'] : $output['bmr_archive_header_txt'];
        $output['bmr_archive_kapper_header_txt']   = isset($data['bmr_archive_kapper_header_txt']) ? $data['bmr_archive_kapper_header_txt'] : $output['bmr_archive_kapper_header_txt'];

        return $output;
    }

    public static function displayAdminPage()
    {
        include_once dirname(__FILE__) . '/partials/options-page.php';
    }

    public static function displayBlackList()
    {
        $blacklist = array();
        include_once dirname(__FILE__) . '/partials/blacklist.php';
    }

    public static function getPageSlug()
    {
        return self::$pageSlug;
    }
}
