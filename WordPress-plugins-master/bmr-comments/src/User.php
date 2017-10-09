<?php
namespace Bmr\Comments;


/**
 * @property bool $hasDefaultAvatar
 * @property string $avatar
 * @property string $initials
 * @property string $isModerator
 * @property string $name
 */
class User extends \WP_User
{
    /**
     * @inherit
     */
    public function __construct($id = 0)
    {
        $id = get_user_by('id', $id);
        parent::__construct($id);
        $id && $this->initExtraData();
    }

    private function initExtraData()
    {
        $this->fillI18nData();
        $this->avatar = $this->getAvatarUrl('150');
        $this->hasDefaultAvatar = $this->isDefaultAvatar();
        $this->isModerator = self::userCanEditComments($this->ID);
        $this->initials = $this->getUserInitials();
        $this->name = $this->getUserName();
    }

    private function fillI18nData()
    {
        if (get_current_blog_id() === 1) {
            return;
        }
        $userdata = get_option('user_' . $this->ID . '_i18ndata', []);

        if ($userdata) {
            isset($userdata['first_name']) && $this->first_name = $userdata['first_name'];
            isset($userdata['last_name']) && $this->last_name = $userdata['last_name'];
            isset($userdata['description']) && $this->description = $userdata['description'];

        }
    }

    public static function userCanEditComments($userId)
    {
        global $blog_id;
        $canEditComments = (bool)get_user_meta($userId, '_can_edit_comments_' . $blog_id, true);
        return user_can($userId, 'manage_options') || $canEditComments;
    }

    public static function userCanChangeSettings($userId)
    {
        global $blog_id;
        $canChangeSettings = (bool)get_user_meta($userId, '_can_change_settings_' . $blog_id, true);
        return user_can($userId, 'manage_options') || $canChangeSettings;
    }

    private function isDefaultAvatar()
    {
        $email = $this->data->user_email;
        $hashkey = md5(strtolower(trim($email)));
        $uri = 'http://www.gravatar.com/avatar/' . $hashkey . '?d=404';

        $data = wp_cache_get($hashkey);
        if (false === $data) {
            $response = wp_remote_head($uri);
            $data = is_wp_error($response) ? 0 : $response['response']['code'];
            wp_cache_set($hashkey, $data);
        }
        return $data == 200 ? false : strpos($this->avatar, 'gravatar.com/avatar') !== false;
    }

    private function getUserInitials()
    {
        $res = mb_substr($this->last_name, 0, 1);
        return $res ? (mb_substr($this->first_name, 0, 1) . $res) : mb_substr($this->data->display_name, 0, 2);
    }

    private function getAvatarUrl($size = '96')
    {
        $avatar = get_avatar($this->ID, $size);
        preg_match("/src=['\"](.*?)['\"]/i", $avatar, $matches);
        return $matches[1];
    }

    private function getUserName($sep = ' ')
    {
        if (!empty($this->first_name) && !empty($this->last_name)) {
            $name = $this->first_name . $sep . $this->last_name;
        } else {
            $name = !empty($this->first_name) ? $this->first_name : $this->data->display_name;
        }
        return $name;
    }
}