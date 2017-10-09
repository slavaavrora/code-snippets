<?php
namespace Bmr\Comments;

/**
 * Class Moderators
 *
 * @package Bmr\Comments
 */
class Moderators extends Table
{
    const EDIT_COMMENTS_FIELD = '_can_edit_comments';
    const CHANGE_SETTINGS_FIELD = '_can_change_settings';

    public function __construct()
    {
        $this->items = [];

        $this->process_action();
        $this->process_bulk_action();

        //Set parent defaults
        parent::__construct([
            'singular' => 'Moderator',
            'plural'   => 'Moderators',
            'ajax'     => false
        ]);
    }


    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="user_ids[]" value="%d" />',
            $item->ID
        );
    }

    public function get_columns()
    {
        $columns = [
            'cb'   => '<input type="checkbox" />',
            'user' => __('Модератор', 'bmr'),
        ];
        return $columns;
    }

    public function get_bulk_actions()
    {
        $actions = [
            'delete'                   => __('Удалить', 'bmr'),
            'allow_change_settings'    => __('Разрешить изменение настроек', 'bmr'),
            'allow_edit_comments'      => __('Разрешить управление комментариями', 'bmr'),
            'disallow_change_settings' => __('Запретить изменение настроек', 'bmr'),
            'disallow_edit_comments'   => __('Запретить управление комментариями', 'bmr'),
        ];
        return $actions;
    }

    public function process_bulk_action()
    {
        if (empty($_POST['user_ids'])) {
            return;
        }
        global $blog_id;
        $ids    = $_POST['user_ids'];
        $action = $this->current_action();

        foreach ($ids as $userId) {
            switch($action) {
                case 'delete':
                    delete_user_meta($userId, self::EDIT_COMMENTS_FIELD . '_' . $blog_id);
                    delete_user_meta($userId, self::CHANGE_SETTINGS_FIELD . '_' . $blog_id);
                    break;
                case 'allow_change_settings':
                    update_user_meta($userId, self::CHANGE_SETTINGS_FIELD . '_' . $blog_id, 1);
                    break;
                case 'allow_edit_comments':
                    update_user_meta($userId, self::EDIT_COMMENTS_FIELD . '_' . $blog_id, 1);
                    break;
                case 'disallow_change_settings':
                    delete_user_meta($userId, self::CHANGE_SETTINGS_FIELD . '_' . $blog_id);
                    break;
                case 'disallow_edit_comments':
                    delete_user_meta($userId, self::EDIT_COMMENTS_FIELD . '_' . $blog_id);
                    break;
            }
        }
    }

    public function extra_tablenav($which)
    {
        if ($which == "top") {
            $roles = ['editor', 'administrator', 'complaints_moderator'];
            $users = [];

            foreach ($roles as $role) {
                $usersList = get_users("role=$role");
                $users = array_merge($users, $usersList);
            }

            foreach($users as $user) {
                $user->user_name = getUserName($user);
            }

            usort($users, function($a, $b) {
                return strcasecmp($a->user_name, $b->user_name);
            });
        ?>
            <div class="alignright">
                <label><?php _e('Пользователь', 'bmr') ?></label>
                <select name="user_id" id="user-to-add">
                    <option value="" selected>—</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?= $user->ID ?>"><?= esc_attr($user->user_name) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="single_action" value="add"/>
                <?php submit_button(__('Добавить', 'bmr'), 'primary', 'submit', false); ?>
            </div>
        <?php
        }
    }

    public function prepare_items()
    {
        global $blog_id;
        $columns               = $this->get_columns();
        $hidden                = [];
        $sortable              = [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        $users = get_users([
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => self::EDIT_COMMENTS_FIELD . '_' . $blog_id,
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => self::CHANGE_SETTINGS_FIELD . '_' . $blog_id,
                    'compare' => 'EXISTS'
                ],
            ]
        ]);

        foreach($users as $user) {
            $user->user_name = getUserName($user);
            $user->can_edit_comments = (int)get_user_meta($user->ID, self::EDIT_COMMENTS_FIELD . '_' . $blog_id, true);
            $user->can_change_settings = (int)get_user_meta($user->ID, self::CHANGE_SETTINGS_FIELD . '_' . $blog_id, true);
        }
        usort($users, function($a, $b) {
            return strcasecmp($a->user_name, $b->user_name);
        });
        $this->items = $users;
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'user':
                ob_start();
                ?>
                    <img src="%s" />
                    <div class="user-info" data-id="%d">
                        <strong><a href="%s">%s</a></strong>
                        <div class="row-caps">
                            <label>
                                <input type="checkbox" class="caps-input" name="edit_comments" %s />
                                <?php _e('Управление комментариями', 'bmr') ?>
                            </label>
                            <label>
                                <input type="checkbox" class="caps-input" name="change_settings" %s />
                                <?php _e('Изменение настроек', 'bmr') ?>
                            </label>
                        </div>
                    </div>
                <?php
                $tpl = ob_get_clean();
                return sprintf(
                    $tpl,
                    getAvatarUrl($item->ID, 32),
                    $item->ID,
                    esc_attr(get_edit_user_link($item->ID)),
                    $item->user_name,
                    checked(1, $item->can_edit_comments, false),
                    checked(1, $item->can_change_settings, false)
                );
                break;

            default:
                return '';
        }
    }

    private function process_action()
    {
        if (empty($_POST['user_id']) || empty($_POST['single_action'])) {
            return;
        }
        global $blog_id;
        $userId = (int)$_POST['user_id'];
        $action = $_POST['single_action'];

        switch($action) {
            case 'add':
            case 'add_edit_comments':
                update_user_meta($userId, self::EDIT_COMMENTS_FIELD . '_' . $blog_id, 1);
                break;
            case 'add_change_settings':
                update_user_meta($userId, self::CHANGE_SETTINGS_FIELD . '_' . $blog_id, 1);
                break;
            case 'remove_edit_comments':
                delete_user_meta($userId, self::EDIT_COMMENTS_FIELD . '_' . $blog_id);
                break;
            case 'remove_change_settings':
                delete_user_meta($userId, self::CHANGE_SETTINGS_FIELD . '_' . $blog_id);
                break;
        }
    }

}
