<?php

/**
 * Class BmrComplaintForm
 */
class BmrComplaintForm
{
    /**
     * @var int
     */
    private $attachmentIds;

    /**
     * @var int
     */
    private $complaintId;

    /**
     * @var string
     */
    private $siteTitle;

    public function __construct()
    {
        $this->siteTitle = get_bloginfo('name');
        $this->siteTitle = !empty($this->siteTitle) ? $this->siteTitle : __('Рейтинг Букмекеров', 'bmr');
        $this->attachmentIds = array();
        $this->complaintId = null;
    }

    public function init()
    {
        // complaint form shortcode
        add_shortcode('complaint_form', array($this, 'complaintFormShortcode'));

        // complaint form submit handler
        add_action('wp_ajax_' . BmrConfig::SUBMIT_ACTION, array($this, 'complaintAction'));
        add_action('wp_ajax_nopriv_' . BmrConfig::SUBMIT_ACTION, array($this, 'complaintAction'));

        add_action('wp_ajax_' . BmrConfig::FILE_ACTION, array($this, 'fileUploadAction'));
        add_action('wp_ajax_nopriv_' . BmrConfig::FILE_ACTION, array($this, 'fileUploadAction'));

        add_action('wp_ajax_' . BmrConfig::FILE_DELETE_ACTION, array($this, 'fileDelAction'));
        add_action('wp_ajax_nopriv_' . BmrConfig::FILE_DELETE_ACTION, array($this, 'fileDelAction'));
    }

    public function fileDelAction()
    {
        $id = (int)sanitize_text_field($_GET['id']);
        $commentId = (int)sanitize_text_field($_GET['comment_id']);

        if ($id != 0) {
            wp_delete_attachment($id, true);
            $response['success'] = true;

            if (!empty($commentId) && $commentId != -1) {
                $fileIds = BmrCommentSystem::getCommentMeta($commentId, 'bmr_comment_file_ids');

                if ($fileIds !== false) {
                    $fileIds = trim(preg_replace("/$id,?/", '', $fileIds), ',');
                    BmrCommentSystem::updateCommentMeta($commentId, 'bmr_comment_file_ids', $fileIds);
                }
            }
        } else {
            $response['success'] = false;
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    public function fileUploadAction()
    {
        header('Content-Type: application/json');
        $response['success'] = false;
        $fileId = '';

        if (!empty($_FILES['bmr_file'])) {
            $message = $this->checkFile($_FILES['bmr_file']);
            if (empty($message)) {
                $fileId = media_handle_upload('bmr_file', 0);
            } else {
                $fileId = new WP_Error('file_upload_error', $message);
            }
            if (!is_wp_error($fileId)) {
                $response['success'] = true;
                $response['file'] = $_FILES['bmr_file'];
                $response['file']['id'] = $fileId;
                $response['file']['size'] = fruitframe_format_bytes($response['file']['size']);
                $response['file']['url_full'] = wp_get_attachment_url($fileId);
                $image = wp_get_attachment_image_src($fileId);
                $response['file']['url_thumb'] = $image[0];
            } else {
                $response['error'] = $fileId->get_error_message();
            }
        } else {
            $response['error'] = __('Файл не выбран', 'bmr');
        }
      //  print_r($response);
        echo json_encode($response);
        exit();
    }

    public function securityAndConditionsCheck()
    {
        $response = array();

        if (!wp_verify_nonce($_POST['security'], BmrConfig::NONCE_ACTION)) {
            $response = array(
                'success' => false,
                'msg' => __('Ошибка безопасности.', 'bmr')
            );
        } elseif (!isset($_POST['conditions'])) {
            $response = array(
                'success' => false,
                'msg' => __('Для отправки формы Вы должны принять условия пользовательского соглашения.', 'bmr')
            );
        }

        if (count($response) !== 0) {
            echo json_encode($response);
            exit();
        }
    }

    /**
     *
     */
    public function complaintAction()
    {
        header('Content-Type: application/json');

        // Security and conditions check
        $this->securityAndConditionsCheck();

        // Data sanitizing
        $data     = $_POST;
        $response = array('success' => false);
        $this->filterInputData($data);

        if (!empty($data['errors'])) {
            $response['errors']  = $data['errors'];
            echo json_encode($response);
            exit();
        }
        $this->emailInBlacklistCheck($data['bmr_email']);

        $complaint = array(
            'post_type'   => BmrConfig::POST_TYPE,
            'post_status' => 'pending',
        );
        $typeName = get_term($data['bmr_complaint_type'], BmrConfig::TAXONOMY_COMPLAINT_TYPE);
        $typeName = $typeName->name;

        $tags = $data['bmr_bookmaker'];

        $complaint['post_title'] = $data['complaint_title'] = BmrHelper::generateComplaintTitle(
            $typeName,
            $data['bmr_bookmaker']
        );
        $complaint['post_content'] = $data['bmr_description'];
        $complaint['post_author']  = 1;

        $dups = $this->getComplaintDups($data);

        $this->complaintId = wp_insert_post($complaint, true);

        if (is_wp_error($this->complaintId)) {
            $response['msg'] = $this->complaintId->get_error_message();
        } else {
            wp_set_post_terms($this->complaintId, $data['bmr_complaint_type'], BmrConfig::TAXONOMY_COMPLAINT_TYPE);
            wp_set_post_terms($this->complaintId, $tags, BmrConfig::TAXONOMY_COMPLAINT_COMPANY);
            $response['success'] = true;

            //$this->sendConfirmation($data);
            $this->sendNotification($data);

            if ($dups) {
                update_field('bmr_complaint_dup', 1, $this->complaintId);
                update_post_meta($this->complaintId, '_bmr_complaints_dup_ids', $dups);
            }

            $attachments = sanitize_text_field($data['bmr_attachments']);

            if (!empty($attachments)) {
                $attachments = explode(',', $attachments);
                $this->attachmentIds = $attachments;

                foreach ($attachments as $media) {
                    wp_update_post(array('ID' => $media, 'post_parent' => $this->complaintId));
                }
            }

            $this->afterComplaintInsert($data);
            $this->sendNewComplaintNotification($data);

            $relatedArr = array();
            $relatedCompaints = BmrRelated::getRelated($this->complaintId, 7);

            if (!empty($relatedCompaints)) {
                foreach($relatedCompaints as $related) {
                    $link      = get_permalink($related->ID);
                    $status    = wp_get_post_terms($related->ID, get_post_type($related->ID) !== BmrConfig::POST_TYPE_KAPPER
                        ? BmrConfig::TAXONOMY_COMPLAINT_STATUS
                        : BmrConfig::TAXONOMY_COMPLAINT_STATUS_KAPPER);
                    $status    = $status[0];
                    $type      = wp_get_post_terms($related->ID, BmrConfig::TAXONOMY_COMPLAINT_TYPE);
                    $type      = $type[0];
                    $date      = get_the_time("d.m.y", $related->ID);
                    $type_link = get_term_link($type->term_id, BmrConfig::TAXONOMY_COMPLAINT_TYPE);


                    $relatedArr[] = array(
                        'title'     => $related->post_title,
                        'link'      => $link,
                        'date'      => $date,
                        'status'    => $status,
                        'type'      => $type,
                        'type_link' => $type_link,
                        'content'   => wp_trim_words($related->post_content, 25)
                    );
                }
            }
            $response['data'] = $relatedArr;
        }
        echo json_encode($response);
        exit();
    }

    /**
     * @param $file
     * @return string
     */
    private function checkFile($file)
    {
        $message = '';
        if (exif_imagetype($file['tmp_name']) === false) {
            $message = __('Вы можете загружать только изображения!', 'bmr');
        } elseif ($file["error"] > 0) {

            switch($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $message = __(
                        'Размер принятого файла превысил максимально допустимый размер, который задан директивой upload_max_filesize конфигурационного файла php.ini.',
                        'bmr'
                    );
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $message = __(
                        'Размер загружаемого файла превысил значение MAX_FILE_SIZE, указанное в HTML-форме.',
                        'bmr'
                    );
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $message = __(
                        'Загружаемый файл был получен только частично.',
                        'bmr'
                    );
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $message = __(
                        'Файл не был загружен.',
                        'bmr'
                    );
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $message = __(
                        'Отсутствует временная папка.',
                        'bmr'
                    );
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $message = __(
                        'Не удалось записать файл на диск.',
                        'bmr'
                    );
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $message = __(
                        'PHP-расширение остановило загрузку файла.',
                        'bmr'
                    );
                    break;
                default:
                    $message = __(
                        'Во время загрузки файла произошла неизвестная ошибка.',
                        'bmr'
                    );
                    break;
            }
        } elseif ($file['size'] > 2050000) {
            $message = __('Пожалуйста, выберите файл размером не более 2 МБ', 'bmr');
        }
        return $message;
    }

    /**
     * @param $data
     */
    private function sendNewComplaintNotification (&$data)
    {
        $subject = __('Ваша жалоба принята', 'bmr');
        $email_title = sprintf(__('Спасибо за использование нашего сервиса! Мы получили вашу жалобу «%s» и скоро она будет обработана, и опубликована на сайте.', 'bmr'), get_the_title($this->complaintId));

        ob_start();
        include_once get_template_directory() . '/templates/emails/notice.php';
        $content = ob_get_clean();

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', __('Уведомление о вашей жалобе', 'bmr'),
                'complaints@bookmakersrating.ru')
        ];
        return \Base\Helpers\Main::sendEmail(get_the_author_meta('user_email', $data['bmr_user_id']), $subject, $content, $headers);
    }
    private function afterComplaintInsert(&$data)
    {
        if (function_exists('update_field')) {
            update_field('bmr_name', $data['bmr_name'], $this->complaintId);
            update_field('bmr_email', $data['bmr_email'], $this->complaintId);
            update_field('bmr_username', $data['bmr_username'], $this->complaintId);
            update_field('bmr_contacted_support', $data['bmr_support'], $this->complaintId);
            $bmr_author_key = \Base\Helpers\Main::getAcfKeyByFieldName('bmr_author');
            if ($bmr_author_key) {
                update_field($bmr_author_key, $data['bmr_user_id'], $this->complaintId);
            } else {
                update_post_meta($this->complaintId, 'bmr_author', $data['bmr_user_id']);
            }
            update_field('bmr_dispute_sum', $data['bmr_dispute_sum'], $this->complaintId);
            update_field('bmr_dispute_currency', $data['bmr_dispute_currency'], $this->complaintId);

            if (!empty($this->attachmentIds)) {
                $attachmentCount = count($this->attachmentIds);
                update_field('bmr_files', $attachmentCount, $this->complaintId);

                for ($i = 0; $i < $attachmentCount; $i++) {
                    update_field('bmr_files_' . $i . '_single', $this->attachmentIds[$i], $this->complaintId);
                }
            }
        } else {
            update_post_meta($this->complaintId, 'bmr_name', $data['bmr_name']);
            update_post_meta($this->complaintId, 'bmr_email', $data['bmr_email']);
            update_post_meta($this->complaintId, 'bmr_username', $data['bmr_username']);
            update_post_meta($this->complaintId, 'bmr_contacted_support', $data['bmr_support']);
            update_post_meta($this->complaintId, 'bmr_author', $data['bmr_user_id']);
            update_post_meta($this->complaintId, 'bmr_dispute_sum', $data['bmr_dispute_sum']);
            update_post_meta($this->complaintId, 'bmr_dispute_currency', $data['bmr_dispute_currency']);

            if (!empty($this->attachmentIds)) {
                $attachmentCount = count($this->attachmentIds);
                $fileUrl         = '';

                for ($i = 0; $i < $attachmentCount; $i++) {
                    $fileUrl = wp_get_attachment_url($this->attachmentIds[$i]);
                    update_post_meta($this->complaintId, 'complaint_file_'. ($i+1) . '_url', $fileUrl);
                }
            }
        }
        delete_transient('complaint_dispute_payments');
        update_post_meta($this->complaintId, '_complaint_bookmaker', $data['bmr_bookmaker']);
    }

    /**
     * @param $data
     * @return bool
     */
    private function sendConfirmation(&$data)
    {
        if (empty($data['bmr_email']) || empty($data['bmr_name'])) {
            return false;
        }
        remove_all_filters( 'wp_mail_from' );
        remove_all_filters( 'wp_mail_from_name' );

        $to        = sprintf('%s<%s>', $data['bmr_name'], $data['bmr_email']);
        $subject   = BmrOptions::option('bmr_form_mail_subject');
        $message   = BmrOptions::option('bmr_form_mail_tpl');
        $from      = sprintf(__('From: %s <%s>', 'bmr'), $this->siteTitle, BmrOptions::option('bmr_email'));
        $headers[] = "Content-type: text/html";
        $headers[] = $from;

        return @wp_mail($to, $subject, $message, $headers);
    }

    /**
     * @param $data
     * @return bool
     */
    private function sendNotification(&$data)
    {
        if (empty($data['bmr_email']) || empty($data['bmr_name'])) {
            return false;
        }
        $emails = [BmrOptions::option('bmr_email')];
        get_current_blog_id() === BLOG_ID_BMR_UA && $emails[] = 'serega_manutd@mail.ru';

        foreach($emails as $email) {
            remove_all_filters( 'wp_mail_from' );
            remove_all_filters( 'wp_mail_from_name' );

            $editLink = admin_url('post.php') . '?post=' . $this->complaintId . '&action=edit';

            $to        = sprintf('%s<%s>', $this->siteTitle, $email);
            $subject   = __('Появилась новая жалоба ожидающая подтверждения', 'bmr');
            $message = sprintf(
                __('Была отправлена новая жалоба (%s). <a href="%s">Перейти к жалобе</a>', 'bmr'),
                $data['complaint_title'],
                $editLink
            );
            $from      = sprintf(__('From: %s <%s>', 'bmr'), $this->siteTitle, BmrOptions::option('bmr_email'));
            $headers[] = "Content-type: text/html";
            $headers[] = $from;

            @wp_mail($to, $subject, $message, $headers);

        }
        return true;
    }

    /**
     * @param $data
     */
    private function filterInputData(&$data)
    {
        $data['errors'] = array();
        // name
        if (!empty($data['bmr_name'])) {
            $data['bmr_name'] = sanitize_text_field($data['bmr_name']);
        } else {
            $data['errors']['bmr_name'] = __('Поле `Имя` не может быть пустым!', 'bmr');
        }
        // email
        if (!empty($data['bmr_email']) && is_email($data['bmr_email'])) {
            $data['bmr_email'] = sanitize_email($data['bmr_email']);
        } else {
            $data['errors']['bmr_email'] = __('Не правильный E-Mail адрес!', 'bmr');
        }
        // bookmaker
        if (!empty($data['bmr_bookmaker'])) {
            $data['bmr_bookmaker'] = sanitize_text_field($data['bmr_bookmaker']);
        } else {
            $data['errors']['bmr_bookmaker'] = __('Поле `Букмекерская контора` не может быть пустым!', 'bmr');;
        }
        // username
        if (!empty($data['bmr_username'])) {
            $data['bmr_username'] = sanitize_text_field($data['bmr_username']);
        } else {
            $data['errors']['bmr_username'] = __('Поле `Логин` не может быть пустым!', 'bmr');;
        }

        // dispute sum
        if (!empty($data['bmr_dispute_sum'])) {

            if (preg_match('/\d+([.,]\d+)?/', $data['bmr_dispute_sum'])) {
                $data['bmr_dispute_sum'] = sanitize_text_field($data['bmr_dispute_sum']);
                $data['bmr_dispute_sum'] = (float)str_replace(',', '.', $data['bmr_dispute_sum']);
            } else {
                $data['errors']['bmr_dispute_sum'] = __('Неправильно заполнено поле `Сумма спора`!', 'bmr');
            }

        } else {
            $data['errors']['bmr_dispute_sum'] = __('Поле `Сумма спора` не может быть пустым!', 'bmr');
        }

        // dispute currency
        if (!empty($data['bmr_dispute_currency'])) {
            $data['bmr_dispute_currency'] = sanitize_text_field($data['bmr_dispute_currency']);
        } else {
            $data['errors']['bmr_dispute_currency'] = __('Поле `Валюта` не может быть пустым!', 'bmr');;
        }

        // dispute description
        if (!empty($data['bmr_description'])) {
            $data['bmr_description'] = wp_kses_post($data['bmr_description']);
        } else {
            $data['errors']['bmr_description'] = __('Поле `Описание спора` не может быть пустым!', 'bmr');;
        }

        // user id
        $data['bmr_user_id'] = !empty($data['bmr_user_id']) ? (int)$data['bmr_user_id'] : 1;

        // complaint type
        if (!empty($data['bmr_complaint_type'])) {
            $data['bmr_complaint_type'] = (int)$data['bmr_complaint_type'];
        } else {
            $data['errors']['bmr_complaint_type'] = __('Поле `Тип спора` не может быть пустым!', 'bmr');
        }
        $data['bmr_support'] = isset($data['bmr_support']) ? sanitize_text_field($data['bmr_support']) : 'no';
    }
    /**
     *  Creating page with complaint form
     */
    public static function createComplaintFormPage()
    {
        $pageExists = post_exists(__('Форма жалобы на букмекерскую контору', 'bmr'));
        if ($pageExists === 0) {
            wp_insert_post(
                array(
                    'post_title' => __('Форма жалобы на букмекерскую контору', 'bmr'),
                    'post_type' => 'page',
                    'post_name' => 'forma-zhalob',
                    'comment_status' => 'closed',
                    'post_status' => 'publish',
                    'post_content' => '[complaint_form]'
                )
            );
        }
    }

    /**
     *
     */
    private function renderComplainForm()
    {
        global $current_user;
        get_currentuserinfo();

        $types      = BmrHelper::getComplaintTypes();
        $usrEmail   = !empty($current_user->user_email) ? esc_attr($current_user->user_email) : '';
        $userId     = $current_user->ID;
        $currencies = BmrHelper::getCurrencyList();

        ob_start();
        include_once dirname(__FILE__) . DS . 'partials' . DS . 'complain-form.php';
        return ob_get_clean();
    }

    /**
     * @return string
     */
    public function complaintFormShortcode()
    {
        return $this->renderComplainForm();
    }

    private function getComplaintDups($data)
    {
        global $wpdb;
        $dups = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT p.ID
                FROM $wpdb->posts p
                LEFT JOIN $wpdb->postmeta pm1
                ON pm1.post_id = p.ID and pm1.meta_key = '_complaint_bookmaker'
                LEFT JOIN $wpdb->postmeta pm2
                ON pm2.post_id = p.ID and pm2.meta_key = 'bmr_email'
                WHERE p.post_type = %s AND p.post_status IN ('publish', 'pending')
                AND pm1.meta_value COLLATE utf8mb4_unicode_ci LIKE %s AND pm2.meta_value COLLATE utf8mb4_unicode_ci LIKE %s",

                BmrConfig::POST_TYPE,
                '%' . $wpdb->esc_like($data['bmr_bookmaker']) . '%',
                '%' . $wpdb->esc_like($data['bmr_email']) . '%'
            )
        );
        return is_array($dups) ? $dups : array();
    }

    private function emailInBlacklistCheck($email) {
        $blacklist = BmrHelper::getBlackList();

        if (!in_array($email, $blacklist, true)) {
            return;
        }
        echo json_encode(array(
            'success' => false,
            'msg'     => __('Извините, вы не можете отправлять жалобы, так как ваш E-Mail находится в черном списке.', 'bmr')
        ));
        exit();
    }

}