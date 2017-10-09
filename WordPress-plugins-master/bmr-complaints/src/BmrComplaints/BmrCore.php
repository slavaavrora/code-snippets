<?php
class BmrCore
{
    /**
     * @var array
     */
    private static $terms;

    /**
     *  Initializes plugin core functions and classes
     */
    public static function init()
    {
        global $wpdb;
        $wpdb->bmr_complaint_commentmeta = "{$wpdb->prefix}bmr_complaint_commentmeta";
        $wpdb->bmr_complaint_comments = "{$wpdb->prefix}bmr_complaint_comments";

        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueuePublicAssets'));
        } else {
            add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueueAdminAssets'));
        }

        // Set default terms for complaint taxonomies
        self::setDefaults();

        // Post types and taxonomy
        add_action('init', array(__CLASS__, 'registerComplaintPostType'));
        add_action('init', array(__CLASS__, 'createComplaintTypeTaxonomy'));
        add_action('init', array(__CLASS__, 'createComplaintStatusTaxonomy'));
        add_action('init', array(__CLASS__, 'createComplaintTagTaxonomy'));
        add_action('init', array(__CLASS__, 'createComplaintCompanyTaxonomy'));
        //add_action('init', array(__CLASS__, 'insertComplaintTerms'));

        if (!is_admin()) {
            add_filter('wp_title', array(__CLASS__, 'pageTitleWithFilters'), 20);
            add_filter('wpseo_title', array(__CLASS__, 'pageTitleWithFilters'), 20);
        }
        // Template redirect
        add_action("template_redirect", array(__CLASS__, 'themeRedirect'));

        if (is_admin()) {
            add_action('admin_menu', array(__CLASS__, 'removeStatusTaxonomyMetabox'));

            add_filter('display_post_states', array(__CLASS__, 'editTitleIfDup'), 10, 2);

            // Custom columns
            // - Create additional columns
            add_filter("manage_" . BmrConfig::POST_TYPE . "_posts_columns", array(__CLASS__, "changeColumns"));
            // - Fill columns with data
            add_action("manage_posts_custom_column", array(__CLASS__, "customColumns"));
            // - Make columns sortable
            add_filter("manage_edit-" . BmrConfig::POST_TYPE . "_sortable_columns",
                array(__CLASS__, "sortableColumns"));
            // - Add filter to custom post type
            add_action('restrict_manage_posts', array(__CLASS__, 'restrictManagePosts'));
            // - Handle filter action
            add_filter('parse_query', array(__CLASS__, 'filterPostTypeRequest'));

            // Add complaints comments metabox
            add_action('add_meta_boxes', array(__CLASS__, 'commentsMetaBox'));
        }

        $form = new BmrComplaintForm();
        $form->init();

        $commentSystem = new BmrCommentSystem();
        $commentSystem->init();

        $options = new BmrOptions();
        $options->init();

        $related = new BmrRelated();
    }

    public static function editTitleIfDup($post_states, $post)
    {
        if (get_post_type($post) === BmrConfig::POST_TYPE) {
            $dups = get_post_meta($post->ID, '_bmr_complaint_dup_ids', true);
            $isDup = (bool)get_post_meta($post->ID, 'bmr_complaint_dup', true);
            if ($isDup && !empty($dups)) {
                $dupUris = '';
                foreach ($dups as $key => $dupId) {
                    if (in_array(get_post_status($dupId), array(false, 'trash'))) {
                        continue;
                    }
                    $dupUris .= '<a href="' .  get_edit_post_link($dupId, '') . '">' . ($key+1) . '</a>, ';
                }
                $dupUris = ' ' . rtrim($dupUris, ', ');
                echo ' - <span style="color:red">(' . __('Дубликат?', 'bmr') . $dupUris . ')</span>';
            }
        }
        return $post_states;
    }

    public static function commentsMetaBox()
    {
        add_meta_box( 'complaints-meta-box', __('Обсуждение жалобы', 'bmr'), array(__CLASS__, 'renderCommentsMetaBox'), BmrConfig::POST_TYPE, 'normal', 'default' );
    }

    public static function pageTitleWithFilters($title, $sep = '-')
    {
        $queryObj = get_queried_object();
        $type = isset($queryObj->taxonomy) && $queryObj->taxonomy === BmrConfig::TAXONOMY_COMPLAINT_STATUS
              ? $queryObj->slug
              : null
        ;
        $type = isset($_GET['complaint_type']) ? $_GET['complaint_type'] : $type;

        if ($type !== null) {
            $siteTitle = ' ' . get_bloginfo( 'name' );
            $sep = !empty($sep) ? $sep : '-';
            $siteTitle = !empty($siteTitle) ? ($sep . ' ' . $siteTitle) : '';

            switch($type) {
                case 'complaint-status-groundless':
                    //Безосновательная
                    $title = __('Безосновательные жалобы ', 'bmr') . $siteTitle;
                    break;
                case 'complaint-status-refused':
                    //Не удовлетворена
                    $title = __('Неудовлетворённые жалобы ', 'bmr') . $siteTitle;
                    break;
                case 'complaint-status-processing':
                    //Обрабатывается
                    $title = __('Жалобы которые обрабатываются ', 'bmr') . $siteTitle;
                    break;
                case 'complaint-status-ignored':
                    //Проигнорирована
                    $title = __('Проигнорированные жалобы ', 'bmr') . $siteTitle;
                    break;
                case 'complaint-status-solved':
                    //Удовлетворена
                    $title = __('Удовлетворённые жалобы ', 'bmr') . $siteTitle;
                    break;
                default:
                    break;
            }
        }
        return $title;
    }

    public static function renderCommentsMetaBox()
    {
        global $post;
        $comments = BmrHelper::getComplaintsComments($post->ID);
        $walker = new BmrCommentWalker();
        $output = $walker->walk($comments,0);

        if (count($comments) > 0) { ?>
            <div id="comments" class="comments-area">
            <ol class="comment-list">
                <?php echo $output; ?>
            </ol>
            </div>
        <?php } else {
            ?><p><?php _e('Нет комменатриев.', 'bmr') ?></p><?php
        }
    }

    public static function setupCommentTables()
    {
        global $wpdb;
        global $charset_collate;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $wpdb->bmr_complaint_commentmeta = "{$wpdb->prefix}bmr_complaint_commentmeta";
        $wpdb->bmr_complaint_comments = "{$wpdb->prefix}bmr_complaint_comments";

        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->bmr_complaint_commentmeta}");
        $sql = "CREATE TABLE {$wpdb->bmr_complaint_commentmeta} (
            meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comment_id bigint(20) unsigned NOT NULL DEFAULT '0',
            meta_key varchar(255) DEFAULT NULL,
            meta_value longtext,
            PRIMARY KEY (meta_id),
            KEY comment_id (comment_id),
            KEY meta_key (meta_key)
        ) $charset_collate; ";
        dbDelta($sql);

        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->bmr_complaint_comments}");
        $sql = "CREATE TABLE {$wpdb->bmr_complaint_comments} (
            comment_ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comment_post_ID bigint(20) unsigned NOT NULL DEFAULT '0',
            comment_author tinytext NOT NULL,
            comment_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            comment_date_gmt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            comment_content text NOT NULL,
            comment_parent bigint(20) unsigned NOT NULL DEFAULT '0',
            comment_visibility tinyint(1) DEFAULT '0',
            user_id bigint(20) unsigned NOT NULL DEFAULT '0',
            reply_to bigint(20) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (comment_ID),
            KEY comment_post_ID (comment_post_ID),
            KEY comment_date_gmt (comment_date_gmt),
            KEY comment_parent (comment_parent)
        ) $charset_collate; ";
        dbDelta($sql);
    }

    /**
     * Set default terms for complaint taxonomies
     */
    public static function setDefaults()
    {
        self::$terms = array(
            BmrConfig::TAXONOMY_COMPLAINT_TYPE   => array(
                'complaint-type-other'         => __('Другое', 'bmr'),
                'complaint-type-payout'        => __('Задержка выплаты', 'bmr'),
                'complaint-type-bonus'         => __('Незачисленный бонус', 'bmr'),
                'complaint-type-wager-dispute' => __('Неправильно рассчитанная ставка', 'bmr'),
                'complaint-type-support-issue' => __('Служба поддержки', 'bmr'),
            ),
            BmrConfig::TAXONOMY_COMPLAINT_STATUS => array(
                'complaint-status-groundless' => __('Безосновательная', 'bmr'),
                'complaint-status-refused'    => __('Не удовлетворена', 'bmr'),
                'complaint-status-processing' => __('Обрабатывается', 'bmr'),
                'complaint-status-ignored'    => __('Проигнорирована', 'bmr'),
                'complaint-status-solved'     => __('Удовлетворена', 'bmr'),
            )
            ,BmrConfig::TAXONOMY_COMPLAINT_STATUS_KAPPER => array(
                'complaint-status-groundless' => __('Безосновательная', 'bmr'),
                'complaint-status-refused'    => __('Не удовлетворена', 'bmr'),
                'complaint-status-processing' => __('Обрабатывается', 'bmr'),
                'complaint-status-ignored'    => __('Проигнорирована', 'bmr'),
                'complaint-status-solved'     => __('Удовлетворена', 'bmr'),
            )
        );
    }

    /**
     *
     */
    public static function enqueuePublicAssets()
    {
        if (!BmrHelper::isComplaints()) {
            return false;
        }

        // STYLES
        wp_enqueue_style(
            BmrConfig::SLUG . '-css',
            plugins_url('/assets/css/main.css', __FILE__)
        );

        wp_enqueue_style(
            BmrConfig::SLUG . '-fileupload-css',
            plugins_url('/assets/js/jquery-file-upload/css/jquery.fileupload.css', __FILE__)
        );

        // SCRIPTS
        wp_deregister_script('jquery');
        wp_register_script('jquery', includes_url('js/jquery/jquery.js'), array(), BMR_PLUGIN_VERSION, true);

        wp_enqueue_script(
            BmrConfig::SLUG . '-jquery-ui.widget',
            plugins_url('/assets/js/jquery-file-upload/js/vendor/jquery.ui.widget.js', __FILE__),
            array('jquery'),
            BMR_PLUGIN_VERSION,
            true
        );
        wp_enqueue_script(
            BmrConfig::SLUG . '-jquery-iframe-transport',
            plugins_url('/assets/js/jquery-file-upload/js/jquery.iframe-transport.js', __FILE__),
            array('jquery'),
            BMR_PLUGIN_VERSION,
            true

        );
        wp_enqueue_script(
            BmrConfig::SLUG . '-jquery-fileupload',
            plugins_url('/assets/js/jquery-file-upload/js/jquery.fileupload.js', __FILE__),
            array('jquery'),
            BMR_PLUGIN_VERSION,
            true
        );

        wp_enqueue_script(
            BmrConfig::SLUG . '-bs3-dropdown',
            plugins_url('/assets/js/bs3-dropdown.js', __FILE__),
            array('jquery'),
            BMR_PLUGIN_VERSION,
            true
        );

        wp_enqueue_script(
            BmrConfig::SLUG . '-fancyselect',
            plugins_url('/assets/js/fancy-select/fancySelect.js', __FILE__),
            array('jquery'),
            BMR_PLUGIN_VERSION,
            true
        );

        wp_enqueue_script('comment-repy');

        wp_enqueue_script(
            BmrConfig::SLUG . '-jquery-form',
            plugins_url('/assets/js/jquery-form/jquery.form.js', __FILE__),
            array('jquery'),
            BMR_PLUGIN_VERSION,
            true
        );

        wp_enqueue_script(
            BmrConfig::SLUG . '-script',
            plugins_url('/assets/js/main.js', __FILE__),
            array('jquery', 'velocity', 'truncate'),
            BMR_PLUGIN_VERSION,
            true
        );

        $formSuccessText = BmrOptions::option('bmr_form_success_txt');

        $postType = get_post_type() !== BmrConfig::POST_TYPE
            ? get_post_type_object(BmrConfig::POST_TYPE_KAPPER)
            : get_post_type_object(BmrConfig::POST_TYPE);
        $postTypeSlug = $postType->rewrite['slug'];

        wp_localize_script(BmrConfig::SLUG . '-script', 'bmr', array(
                'security' => wp_create_nonce(BmrConfig::NONCE_ACTION),
                'action' => BmrConfig::SUBMIT_ACTION,
                'comment_action' => BmrConfig::COMMENT_ACTION,
                'file_action' => BmrConfig::FILE_ACTION,
                'file_delete_action' => BmrConfig::FILE_DELETE_ACTION,
                'form_success_txt' => $formSuccessText,
                'complaint_post_type_slug' => $postTypeSlug
            )
        );
    }

    /**
     *
     */
    public static function enqueueAdminAssets()
    {
        global $post_type;

        if ($post_type == BmrConfig::POST_TYPE) {
            wp_enqueue_style(
                BmrConfig::SLUG . '-comments-css',
                plugins_url('/assets/css/comments.css', __FILE__)
            );
            wp_enqueue_style(
                'awesome_font',
                get_template_directory_uri() . '/assets/fonts/font-awesome/css/font-awesome.css',
                array(),
                FRUITFRAME_THEME_VERSION
            );
        }

        //global $pagenow;
        //if ($pagenow == BmrOptions::getPageSlug()) {
    }

    /**
     *  Registers complaint post type
     */
    public static function registerComplaintPostType()
    {
        $post_type_args = array(
            'labels'      => array(
                'name'               => __('Жалобы', 'bmr'),
                'singular_name'      => __('Жалоба', 'bmr'),
                'add_new'            => __('Добавить', 'bmr'),
                'add_new_item'       => __('Добавить жалобу', 'bmr'),
                'edit_item'          => __('Редактировать', 'bmr'),
                'new_item'           => __('Новая жалоба', 'bmr'),
                'view_item'          => __('Просмотр', 'bmr'),
                'search_items'       => __('Поиск жалоб', 'bmr'),
                'not_found'          => __('Жалобы не найдены', 'bmr'),
                'not_found_in_trash' => __('Жалобы не найдены в корзине', 'bmr'),
                'parent_item_colon'  => ''
            ),
            'public'      => true,
            'has_archive' => true,
            'supports'    => array('title', 'editor', 'thumbnail', 'comments', 'author'),
            'taxonomies'  => array(
                BmrConfig::TAXONOMY_COMPLAINT_TYPE,
                BmrConfig::TAXONOMY_COMPLAINT_STATUS,
                BmrConfig::TAXONOMY_COMPLAINT_TAG,
                BmrConfig::TAXONOMY_COMPLAINT_COMPANY
            ),
            'rewrite' => array('slug' => 'complaints'),
            'capability_type' => array('complaint', 'complaints'),
            'capabilities' => array(
                'edit_post' => 'edit_complaint',
                'edit_posts' => 'edit_complaints',
                'edit_others_posts' => 'edit_other_complaints',
                'edit_published_posts' => 'edit_published_complaints',
                'edit_private_posts' => 'edit_private_complaints',
                'publish_posts' => 'publish_complaints',
                'read_post' => 'read_complaint',
                'read_private_posts' => 'read_private_complaints',
                'delete_post' => 'delete_complaint',
                'delete_posts' => 'delete_complaints',
                'delete_others_posts' => 'delete_others_complaints',
                'delete_private_posts' => 'delete_private_complaints',
                'delete_published_posts' => 'delete_published_complaints'
            ),
            'map_meta_cap' => true
        );


        register_post_type(BmrConfig::POST_TYPE, $post_type_args);
        $post_type_args['taxonomies'] = [BmrConfig::TAXONOMY_COMPLAINT_STATUS];
        $post_type_args['rewrite'] = ['slug' => 'kapper_complaints'];
        $post_type_args['labels'] = array(
            'name'               => __('Жалобы на капперов', 'bmr'),
            'singular_name'      => __('Жалоба на каппера', 'bmr'),
            'add_new'            => __('Добавить', 'bmr'),
            'add_new_item'       => __('Добавить жалобу', 'bmr'),
            'edit_item'          => __('Редактировать', 'bmr'),
            'new_item'           => __('Новая жалоба', 'bmr'),
            'view_item'          => __('Просмотр', 'bmr'),
            'search_items'       => __('Поиск жалоб', 'bmr'),
            'not_found'          => __('Жалобы не найдены', 'bmr'),
            'not_found_in_trash' => __('Жалобы не найдены в корзине', 'bmr'),
            'parent_item_colon'  => ''
        );
        register_post_type(BmrConfig::POST_TYPE_KAPPER, $post_type_args);
    }

    /**
     * Registers complaint type taxonomy
     */
    public static function createComplaintTypeTaxonomy()
    {
        $labels = array(
            'name'              => __('Тип жалобы', 'bmr'),
            'singular_name'     => __('Тип жалобы', 'bmr'),
            'search_items'      => __('Искать тип жалобы', 'bmr'),
            'all_items'         => __('Все типы жалоб', 'bmr'),
            'parent_item'       => __('Тип родителя', 'bmr'),
            'parent_item_colon' => __('Тип родителя:', 'bmr'),
            'edit_item'         => __('Изменить тип жалобы', 'bmr'),
            'update_item'       => __('Обновить тип жалобы', 'bmr'),
            'add_new_item'      => __('Добавить тип жалобы', 'bmr'),
            'new_item_name'     => __('Новый тип жалобы', 'bmr'),
            'menu_name'         => __('Типы жалоб', 'bmr'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'public'            => true,
            'show_ui'           => true,
            'show_in_nav_menus' => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'type'),
            'capabilities' => array(
                'manage_terms' => 'edit_complaints',
                'edit_terms' => 'edit_complaints',
                'delete_terms' => 'edit_complaints',
                'assign_terms' => 'edit_complaints',
            ),
        );
        register_taxonomy(BmrConfig::TAXONOMY_COMPLAINT_TYPE, BmrConfig::POST_TYPE, $args);
    }

    /**
     * Registers complaint status taxonomy
     */
    public static function createComplaintStatusTaxonomy()
    {
        $labels = array(
            'name'              => __('Статус жалобы', 'bmr'),
            'singular_name'     => __('Статус жалобы', 'bmr'),
            'search_items'      => __('Искать статус жалобы', 'bmr'),
            'all_items'         => __('Все статусы жалоб', 'bmr'),
            'parent_item'       => __('Статус родителя', 'bmr'),
            'parent_item_colon' => __('Статус родителя:', 'bmr'),
            'edit_item'         => __('Изменить статус жалобы', 'bmr'),
            'update_item'       => __('Обновить статус жалобы', 'bmr'),
            'add_new_item'      => __('Добавить статус жалобы', 'bmr'),
            'new_item_name'     => __('Новый статус жалобы', 'bmr'),
            'menu_name'         => __('Статусы жалоб', 'bmr'),
        );

        $args    = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'public'            => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'status'),
            'capabilities' => array(
                'manage_terms' => 'edit_complaints',
                'edit_terms' => 'edit_complaints',
                'delete_terms' => 'edit_complaints',
                'assign_terms' => 'edit_complaints',
            ),
        );
        register_taxonomy(BmrConfig::TAXONOMY_COMPLAINT_STATUS, BmrConfig::POST_TYPE, $args);
        $args['rewrite'] = ['slug' => 'kapper_status'];
        $args['labels']['name'] = __('Статус каперской жалобы', 'bmr');
        $args['labels']['singular_name'] = __('Статус каперской жалобы', 'bmr');
        register_taxonomy(BmrConfig::TAXONOMY_COMPLAINT_STATUS_KAPPER, BmrConfig::POST_TYPE_KAPPER, $args);
    }

    public static function createComplaintTagTaxonomy()
    {
        $labels = array(
            'name'              => __('Метки (Жалобы)', 'bmr'),
            'singular_name'     => __('Метка', 'bmr'),
            'search_items'      => __('Поиск меток', 'bmr'),
            'all_items'         => __('Метки', 'bmr'),
            'edit_item'         => __('Изменить метку', 'bmr'),
            'update_item'       => __('Обновить метку', 'bmr'),
            'add_new_item'      => __('Добавить новую метку', 'bmr'),
            'new_item_name'     => __('Новая метка', 'bmr'),
            'menu_name'         => __('Метки', 'bmr'),
        );

        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'public'            => true,
            'query_var'         => true,
            'show_tagcloud'     => true,
            'rewrite'           => array('slug' => 'complaint-tag'),
            'capabilities' => array(
                'manage_terms' => 'edit_complaints',
                'edit_terms' => 'edit_complaints',
                'delete_terms' => 'edit_complaints',
                'assign_terms' => 'edit_complaints',
            ),
        );
        register_taxonomy(BmrConfig::TAXONOMY_COMPLAINT_TAG, BmrConfig::POST_TYPE, $args);
    }

    public static function createComplaintCompanyTaxonomy()
    {
        $labels = array(
            'name'              => __('Компания', 'bmr'),
            'singular_name'     => __('Компания', 'bmr'),
            'search_items'      => __('Поиск компаний', 'bmr'),
            'all_items'         => __('Компании', 'bmr'),
            'edit_item'         => __('Изменить компанию', 'bmr'),
            'update_item'       => __('Обновить компанию', 'bmr'),
            'add_new_item'      => __('Добавить новую компанию', 'bmr'),
            'new_item_name'     => __('Новая компания', 'bmr'),
            'menu_name'         => __('Компании', 'bmr'),
        );

        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'public'            => true,
            'query_var'         => true,
            'show_tagcloud'     => true,
            'rewrite'           => array('slug' => 'complaint-company'),
            'capabilities' => array(
                'manage_terms' => 'edit_complaints',
                'edit_terms' => 'edit_complaints',
                'delete_terms' => 'edit_complaints',
                'assign_terms' => 'edit_complaints',
            ),
        );
        register_taxonomy(BmrConfig::TAXONOMY_COMPLAINT_COMPANY, BmrConfig::POST_TYPE, $args);
    }

    /**
     *  Inserts default terms into taxonomies
     */
    public static function insertComplaintTerms()
    {
        foreach (self::$terms as $taxonomy => $terms) {
            foreach ($terms as $slug => $term) {
                if ($id = \Base\Helpers\Cache::termExists($term, $taxonomy)) {
                    continue;
                } else {
                    wp_insert_term($term, $taxonomy, array('slug' => $slug));
                }
            }
        }
    }

    public static function themeRedirect()
    {
        global $wp;
        $postType = array_key_exists('post_type', $wp->query_vars) ? $wp->query_vars['post_type'] : get_post_type();
        $isComplaintPostType = ($postType === BmrConfig::POST_TYPE || $postType === BmrConfig::POST_TYPE_KAPPER);

        // Complaint post type single post
        if ($isComplaintPostType && is_single()) {
            $tplFileName = 'single-complaint.php';
            if (file_exists(BMR_PLUGIN_TEMPLATE_DIR . DS . $tplFileName)) {
                $return_template = BMR_PLUGIN_TEMPLATE_DIR . DS . $tplFileName;
                self::doThemeRedirect($return_template);
            }
            // Complaint taxonomies
        } elseif (is_tax(
            array(
                BmrConfig::TAXONOMY_COMPLAINT_TYPE,
                BmrConfig::TAXONOMY_COMPLAINT_STATUS,
                BmrConfig::TAXONOMY_COMPLAINT_STATUS_KAPPER,
                BmrConfig::TAXONOMY_COMPLAINT_TAG,
                BmrConfig::TAXONOMY_COMPLAINT_COMPANY
            ))
        ) {
            $tplFileName = 'taxonomy.php';
            if (file_exists(BMR_PLUGIN_TEMPLATE_DIR . DS . $tplFileName)) {
                $return_template = BMR_PLUGIN_TEMPLATE_DIR . DS . $tplFileName;
                self::doThemeRedirect($return_template);
            }
        } elseif ($isComplaintPostType && is_archive()) {
            $tplFileName = 'archive-complaint.php';
            if (file_exists(BMR_PLUGIN_TEMPLATE_DIR . DS . $tplFileName)) {
                $return_template = BMR_PLUGIN_TEMPLATE_DIR . DS . $tplFileName;
                self::doThemeRedirect($return_template);
            }
        }
    }

    public static function doThemeRedirect($url)
    {
        include_once($url);
        die;
    }

    /**
     * * Fires on plugin activation
     */
    public static function activate()
    {
        self::setupCommentTables();
        BmrOptions::setDefaultSettings();
        BmrComplaintForm::createComplaintFormPage();

        $roles = ['administrator'];
        $caps = [
            'edit_complaint',
            'edit_complaints',
            'edit_other_complaints',
            'edit_published_complaints',
            'edit_private_complaints',
            'publish_complaints',
            'read_complaint',
            'read_private_complaints',
            'delete_complaint',
            'delete_complaints',
            'delete_others_complaints',
            'delete_private_complaints',
            'delete_published_complaints'
        ];
        foreach ($roles as $role) {
            $role = get_role($role);
            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    /**
     * Fires on plugin deactivation
     */
    public static function deactivate()
    {
        $roles = ['administrator'];
        $caps = [
            'edit_complaint',
            'edit_complaints',
            'edit_other_complaints',
            'edit_published_complaints',
            'edit_private_complaints',
            'publish_complaints',
            'read_complaint',
            'read_private_complaints',
            'delete_complaint',
            'delete_complaints',
            'delete_others_complaints',
            'delete_private_complaints',
            'delete_published_complaints'
        ];
        foreach ($roles as $role) {
            $role = get_role($role);
            foreach ($caps as $cap) {
                $role->remove_cap($cap);
            }
        }
    }

    /**
     * @param $cols
     * @return array
     */
    public static function changeColumns($cols)
    {
        $date = $cols['date'];
        unset($cols['date']);
        unset($cols['author']);
        $cols['views'] = __('Просмотры', 'bmr');

        $new_columns = array(
            'bmr_author' => __('Автор жалобы', 'bmr'),
            'bookmaker' => __('Ответчик', 'bmr'),
            'date' => $date,
        );
        return array_merge($cols, $new_columns);
    }

    public static function customColumns($column, $post_id = '')
    {
        global $post;
       if ($column == 'bookmaker') {
           $userId = get_post_meta($post->ID, 'bmr_bookmaker', true);
           $user = get_user_by('id',$userId);
           echo !empty($user->display_name) ? $user->display_name : '—';
        } elseif ($column == 'bmr_author') {
           $userId = get_post_meta($post->ID, 'bmr_author', true);
           $user = get_user_by('id',$userId);
           echo !empty($user->display_name) ? $user->display_name : '—';
       }
    }

    public static function sortableColumns()
    {
        return array(
            'taxonomy-bmr_complaint_status' => 'taxonomy-bmr_complaint_status',
            'bookmaker'                     => 'bookmaker',
            'author'                        => 'author',
            'taxonomy-bmr_type'             => 'taxonomy-bmr_type'
        );
    }

    public static function restrictManagePosts()
    {
        global $typenow, $pagenow;
        $post_types = get_post_types(array( '_builtin' => false ));

        if (in_array($typenow, $post_types)) {
            $filters = get_object_taxonomies($typenow);

            foreach ($filters as $tax_slug) {
                $tax_obj = get_taxonomy($tax_slug);
                wp_dropdown_categories(array(
                    'show_option_none' => __($tax_obj->label, 'bmr'),
                    'taxonomy'         => $tax_slug,
                    'name'             => $tax_obj->name,
                    'orderby'          => 'name',
                    'selected'         => isset($_GET[$tax_slug]) ? $_GET[$tax_slug] : "",
                    'hierarchical'     => $tax_obj->hierarchical,
                    'show_count'       => false,
                    'hide_empty'       => true
                ));
            }
        }
        if ( 'edit.php' == $pagenow && BmrConfig::POST_TYPE == $typenow) {
            $bookmakers = BmrHelper::getMetaValues('bmr_bookmaker', BmrConfig::POST_TYPE);
            if (!empty($bookmakers)) {
                echo '<select name="complaint_bookmaker">';
                echo ' <option value="" selected="selected">' . __('Ответчик', 'bmr') . '</option>';
                foreach ($bookmakers as $v)  {
                    $user = get_user_by('id',$v);
                    $name = !empty($user->display_name) ? $user->display_name : $user->first_name . ' ' . $user->last_name;
                    $name = trim($name);

                    if ($user) {
                        $selected = selected($_GET['complaint_bookmaker'], $v);
                        echo "<option value='$v' $selected>$name</option>";
                    }
                }
                echo '</select>';
            }
        }
    }

    public static function filterPostTypeRequest($query)
    {
        global $pagenow, $typenow;
        $post_types = get_post_types(array( '_builtin' => false ));
        $filters = get_object_taxonomies($typenow);

        if (in_array($typenow, $post_types)) {
            $filters = get_object_taxonomies( $typenow );
            foreach ( $filters as $tax_slug ) {
                $var = &$query->query_vars[$tax_slug];
                if ( isset( $var ) ) {
                    if (is_numeric($var)) {
                        $term = get_term_by('id', $var, $tax_slug);
                    } else {
                        $term = get_term_by('slug', $var, $tax_slug);
                    }
                    $var = $term->slug;
                }
            }
        }
        if ( 'edit.php' == $pagenow && BmrConfig::POST_TYPE == $typenow) {
            if (isset($query->query_vars['s'])) {
                $searchQuery = $query->query_vars['s'];

                if (strpos($searchQuery, '@') !== false) {
                    unset($query->query_vars['s']);
                    $query->query_vars['meta_query'][] = array(
                        'key'   => 'bmr_email',
                        'value' => $searchQuery
                    );
//                    $metaQueryCnt++;
                }
            }
            if (isset($_GET['complaint_bookmaker'])) {
                $bookmaker = $_GET['complaint_bookmaker'];

                if (!empty($bookmaker)) {
                    $query->query_vars['meta_query'][] = array(
                        'key'   => 'bmr_bookmaker',
                        'value' => $bookmaker
                    );
                }
            }
        }
    }

    /**
     * Removes status taxonomy metabox in post edit screen
     */
    public static function removeStatusTaxonomyMetabox()
    {
        remove_meta_box('bmr_complaint_statusdiv', BmrConfig::POST_TYPE, 'side');
        remove_meta_box('bmr_kapper_complaint_statusdiv', BmrConfig::POST_TYPE_KAPPER, 'side');
    }
}
