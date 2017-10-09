<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class BmrEmailListTable extends WP_List_Table
{
    /**
     * @var array
     */
    public $blacklist;

    const OPTION_NAME   = 'bmr_complaints_blacklist';
    const ADD_ACTION    = 'add_to_blacklist';
    const REMOVE_ACTION = 'remove_from_blacklist';

    function __construct()
    {
        $this->items     = array();
        $this->blacklist = $this->get_blacklist();

        $this->process_action();
        $this->process_bulk_action();

        //Set parent defaults
        parent::__construct(array(
            'singular' => 'E-Mail',
            'plural'   => 'E-Mails',
            'ajax'     => false
        ));
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
        $columns = array(
            'cb'    => '<input type="checkbox" />',
            'email' => 'E-Mail'
        );
        return $columns;
    }

    public function get_bulk_actions()
    {
        $actions = array(
            'delete'    => 'Удалить'
        );
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
            <input type="email" name="email_to_blacklist" id="email_to_blacklist"/>
            <input name="single-action" type="hidden" value="<?php echo self::ADD_ACTION ?>"/>
            <?php submit_button('Добавить', 'primary', 'submit', false); ?>
        <?php
        }
    }

    public function prepare_items()
    {
        $columns               = $this->get_columns();
        $hidden                = array();
        $sortable              = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

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
        $email = sanitize_email($_POST['email_to_blacklist']);

        if ($_POST['single-action'] === self::ADD_ACTION) {
            $this->addToBlacklist($email);

        } elseif ($_POST['single-action'] === self::REMOVE_ACTION) {
            $this->removeFromBlacklist($email);
        }
    }

    private function addToBlacklist($email)
    {
        $this->blacklist = $this->get_blacklist();

        if (!in_array($email, $this->blacklist, true)) {
            $this->blacklist[] = $email;
            update_option(self::OPTION_NAME, $this->blacklist);
        }
    }

    private function removeFromBlacklist($email)
    {
        $this->blacklist = $this->get_blacklist();
        $key = array_search($email, $this->blacklist);

        if ($key !== false) {
            unset($this->blacklist[$key]);
            update_option(self::OPTION_NAME, $this->blacklist);
        }
    }

    private function removeFromBlackListById($id)
    {
        $this->blacklist = $this->get_blacklist();
        $key = (int)$id-1;
        unset($this->blacklist[$key]);
    }

    private function bulkRemove($ids)
    {
        array_map(array($this, 'removeFromBlackListById'), $ids);
        $this->blacklist = array_values($this->blacklist);
        update_option(self::OPTION_NAME, $this->blacklist);
    }

    public function get_blacklist()
    {
        return isset($this->blacklist) ? $this->blacklist : get_option(self::OPTION_NAME, array());
    }
}