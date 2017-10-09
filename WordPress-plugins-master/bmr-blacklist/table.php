<?php
namespace Bmr\Blacklist;

class Table extends \WP_List_Table
{
    /**
     * @var array
     */
    public $blacklist;

    const OPTION_NAME   = 'bmr_email_blacklist';
    const ADD_ACTION    = 'add_to_blacklist';
    const REMOVE_ACTION = 'remove_from_blacklist';

    function __construct()
    {
        $this->items     = array();
        $this->blacklist = $this->getBlacklist();

        $this->process_action();
        $this->process_bulk_action();

        //Set parent defaults
        parent::__construct([
            'singular' => 'E-Mail',
            'plural'   => 'E-Mails',
            'ajax'     => false
        ]);
    }


    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/
            $this->_args['singular'],
            /*$2%s*/
            $item['ID']
        );
    }

    public function get_columns()
    {
        $columns = [
            'cb'    => '<input type="checkbox" />',
            'email' => 'E-Mail'
        ];
        return $columns;
    }

    public function get_bulk_actions()
    {
        $actions = [
            'delete'    => __('Удалить', 'bmr')
        ];
        return $actions;
    }

    public function process_bulk_action()
    {
        if (empty($_POST['e-mail'])) {
            return;
        }
        $email_ids = $_POST['e-mail'];

        if ('delete' === $this->current_action()) {
            $this->bulkRemove($email_ids);
        }
    }

    public function extra_tablenav($which)
    {
        if ($which == "top") {
            ?>
            <input type="text" name="email_to_blacklist" id="email_to_blacklist"/>
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

        $emailsCnt = count($this->blacklist);
        for($i = 0; $i < $emailsCnt; $i++) {
            $this->items[$i]['ID'] = $i+1;
            $this->items[$i]['email'] = $this->blacklist[$i];
        }
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'email':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    private function process_action()
    {
        if (empty($_POST['email_to_blacklist']) || empty($_POST['single-action'])) {
            return;
        }
        $email = sanitize_text_field($_POST['email_to_blacklist']);

        if ($_POST['single-action'] === self::ADD_ACTION) {
            $this->addToBlacklist($email);

        } elseif ($_POST['single-action'] === self::REMOVE_ACTION) {
            $this->removeFromBlacklist($email);
        }
    }

    private function addToBlacklist($email)
    {
        $this->blacklist = $this->getBlacklist();

        if (!in_array($email, $this->blacklist, true)) {
            $this->blacklist[] = $email;
            update_site_option(self::OPTION_NAME, $this->blacklist);
        }
    }

    private function removeFromBlacklist($email)
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
}