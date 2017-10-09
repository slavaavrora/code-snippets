<?php

namespace Bmr\Sync;

if (!class_exists('\\WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Table extends \WP_List_Table
{
    protected $postType;

    public function __construct()
    {
        $this->postType = !empty($_GET['post_type']) ? $_GET['post_type'] : false;
        $this->items    = [];

        //Set parent defaults
        parent::__construct([
            'singular' => 'Post',
            'plural'   => 'Posts',
            'ajax'     => false
        ]);
    }

    /**
     * Render checkbox field
     * @param $item
     * @return string
     */
    public function column_cb($item)
    {
        $id = $this->postType === 'bookreviews' && !isset($_GET['transfer']) ? $item['ID_original'] : $item['ID'];
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/
            $this->_args['plural'],
            /*$2%s*/
            $id
        );
    }

    /**
     * Columns labels
     * @return array
     */
    public function get_columns()
    {
        $columns['cb'] = '<input type="checkbox" />';
        if ($this->postType === 'bookreviews' && !isset($_GET['transfer']) ) {
            $columns['post_title'] = __('Заголовок (оригинал)', 'bmr');
            $columns['post_title2'] = __('Заголовок (текущий)', 'bmr');
        } else {
            $columns['post_title'] = __('Заголовок', 'bmr');
        }
        $columns['status'] = __('Статус', 'bmr');
        return $columns;
    }

    /**
     * Bulk actions dropdown
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = [
            'sync'    => 'Синхронизировать'
        ];
        return $actions;
    }

    /**
     * Add extra markup in the toolbars before or after the list
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
     */
    public function extra_tablenav($which)
    {
        if ($which === "top") {

        } elseif ($which === 'bottom') {

        }
    }

    /**
     * Preparing columns, items
     */
    public function prepare_items()
    {
        switch($this->postType) {
            case 'bookreviews':
                $postsFetcher = isset($_GET['transfer']) ? new Posts($this->postType) : new Bookmakers();
                break;
            case 'acf':
                $postsFetcher = new Acf();
                break;
            default:
                $postsFetcher = new Posts($this->postType);
                break;
        }

        // Pagination
        $per_page     = $this->get_items_per_page('posts_per_page', 10);
        $current_page = $this->get_pagenum();
        $total_items  = $postsFetcher->count();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $columns               = $this->get_columns();
        $hidden                = $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        // Prepare items
        $this->items = $postsFetcher->get($per_page, (($current_page-1)*$per_page));
    }

    /**
     * Render field by column name
     *
     * @param $item
     * @param $column_name
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        global $blog_id;
        switch ($column_name) {
            case 'post_title':
                return sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    get_admin_url(1, 'post.php?post=' . $item['ID_original'] . '&action=edit'),
                    $item[$column_name]
                );
            case 'post_title2':
                return sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    get_admin_url($blog_id, 'post.php?post=' . $item['ID'] . '&action=edit'),
                    $item['post_title']
                );
            case 'status':
                $id = $this->postType === 'bookreviews' && !isset($_GET['transfer']) ? $item['ID_original'] : $item['ID'];
                $status = isset($item['status']) ? $item['status'] :  __('Ожидает синхронизации', 'bmr');
                return sprintf('<span id="status-%d">%s</span>', $id, $status);
            default:
                return '';
        }
    }
}
