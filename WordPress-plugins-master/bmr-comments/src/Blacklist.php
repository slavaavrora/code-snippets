<?php
namespace Bmr\Comments;

/**
 * Class Blacklist
 *
 * @package Bmr\Comments
 */
class Blacklist extends Table
{
    /**
     * @var array
     */
    public $blacklist;

    const OPTION_NAME   = 'bmr_comments_blacklist';
    const ADD_ACTION    = 'add_to_blacklist';
    const REMOVE_ACTION = 'remove_from_blacklist';

    public function __construct()
    {
        $this->items     = [];
        $this->blacklist = $this->getBlacklist();

        $this->process_action();
        $this->process_bulk_action();

        //Set parent defaults
        parent::__construct([
            'singular' => 'Blacklist',
            'plural'   => 'Blacklist',
            'ajax'     => false
        ]);
    }

    public function get_columns()
    {
        $columns = [
            'cb'   => '<input type="checkbox" />',
            'user' => __('Пользователь', 'bmr')
        ];
        return $columns;
    }

    public function get_bulk_actions()
    {
        $actions = [
            'delete' => __('Убрать из списка', 'bmr')
        ];
        return $actions;
    }

    public function process_bulk_action()
    {
        if (empty($_POST['blacklist'])) {
            return;
        }
        $ids = $_POST['blacklist'];

        if ('delete' === $this->current_action()) {
            $this->bulkRemove($ids);
        }
    }

    public function extra_tablenav($which)
    {
        if ($which == "top") {
            ?>
            <input type="text" name="user_to_blacklist" id="email_to_blacklist"/>
            <input name="single-action" type="hidden" value="<?php echo self::ADD_ACTION ?>"/>
            <?php submit_button(__('Добавить', 'bmr'), 'primary', 'submit', false); ?>
            <?php
        }
    }

    public function prepare_items()
    {
        $columns               = $this->get_columns();
        $hidden                = [];
        $sortable              = [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        $cnt = count($this->blacklist);
        for($i = 0; $i < $cnt; $i++) {
            $this->items[$i]->ID = $i+1;
            $this->items[$i]->user = $this->blacklist[$i];
        }
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/
            $this->_args['singular'],
            /*$2%s*/
            $item->ID
        );
    }

    public function column_user($item)
    {
        ob_start();

        if (filter_var($item->user, FILTER_VALIDATE_EMAIL) !== false) {
            $id = get_user_by('email', $item->user);
            $id && $item->user = $id;
        }

        if (filter_var($item->user, FILTER_VALIDATE_IP) !== false
            || filter_var($item->user, FILTER_VALIDATE_EMAIL) !== false)
        {
            $avatarUri = getAvatarUrl(0);
            ?>
            <img src="<?= $avatarUri ?>" />
            <div class="user-info">
                <strong><?= $item->user ?></strong>
            </div>
            <?php
        } elseif (is_numeric($item->user)) {
            $user = new User($item->user);
            $userUri = esc_attr(get_edit_user_link($user->ID));
            ?>
            <img src="<?= $user->avatar ?>" />
            <div class="user-info" data-id="<?= $user->ID ?>">
                <strong><a href="<?= $userUri ?>"><?= $user->name ?></a></strong>
            </div>
            <?php
        }
        return ob_get_clean();
    }

    private function process_action()
    {
        if (empty($_POST['user_to_blacklist']) || empty($_POST['single-action'])) {
            return;
        }
        $user = sanitize_text_field($_POST['user_to_blacklist']);

        if ($_POST['single-action'] === self::ADD_ACTION) {
            $this->addToBlacklist($user);

        } elseif ($_POST['single-action'] === self::REMOVE_ACTION) {
            $this->removeFromBlacklist($user);
        }
    }

    public function addToBlacklist($user)
    {
        $this->blacklist = $this->getBlacklist();

        if (filter_var($user, FILTER_VALIDATE_EMAIL) !== false) {
            $id = get_user_by('email', $user);
            $id && $user = $id;
        }

        if (!in_array($user, $this->blacklist)) {
            $this->blacklist[] = $user;
            update_site_option(self::OPTION_NAME, $this->blacklist);
        }
    }

    public function removeFromBlacklist($email)
    {
        $this->blacklist = $this->getBlacklist();
        $key = array_search($email, $this->blacklist);

        if ($key !== false) {
            unset($this->blacklist[$key]);
            update_site_option(self::OPTION_NAME, $this->blacklist);
        }
    }

    private function removeFromBlackListById($id)
    {
        $this->blacklist = $this->getBlacklist();
        $key = (int)$id-1;
        unset($this->blacklist[$key]);
    }

    private function bulkRemove($ids)
    {
        array_map([$this, 'removeFromBlackListById'], $ids);
        $this->blacklist = array_values($this->blacklist);
        update_site_option(self::OPTION_NAME, $this->blacklist);
    }

    public function getBlacklist()
    {
        return isset($this->blacklist) ? $this->blacklist : get_site_option(self::OPTION_NAME, []);
    }

    public function isBanned($comment)
    {
        $ip    = $comment->comment_author_IP;
        $email = $comment->comment_author_email;
        $id    = $comment->user_id;

        return in_array($ip, $this->blacklist, true)
               || in_array($email, $this->blacklist, true)
               || in_array($id, $this->blacklist);
    }
}
