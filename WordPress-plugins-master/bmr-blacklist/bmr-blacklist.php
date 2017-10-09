<?php
/*
Plugin Name: Bookmakers E-Mail Blacklist
Description: Allows to add e-mails, domains to which the mail will not be sent
Version: 1.0
*/
namespace Bmr\Blacklist;

if (!defined('WPINC')) {
    die;
}

class Blacklist
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_filter('wp_mail', [$this, 'filterEmail']);
    }

    public function addMenu()
    {
        add_options_page(
            __('Черный список', 'bmr'),
            __('Черный список', 'bmr'),
            'manage_network',
            'mail-blacklist',
            [$this, 'renderPage']
        );
    }

    public function renderPage()
    {
        ?>
        <div class="wrap">
            <h2><?php echo esc_html(get_admin_page_title()) ?></h2>
            <br>
            <div class="row">
                <?php
                $table = new Table();
                $table->prepare_items();
                ?>
                <form id="email-actions" method="POST">
                    <?php $table->display(); ?>
                </form>
            </div>
        </div>
        <?php
    }

    public function filterEmail($data)
    {
        $blacklist  = get_site_option(Table::OPTION_NAME, []);
        $blacklist = array_map('preg_quote', $blacklist);
        $blacklist  = implode('|', $blacklist);
        $mails      = !is_array($data['to']) ? explode(',', $data['to']) : $data['to'];
        $mails      = preg_grep('#(' . $blacklist . ')#', $mails, PREG_GREP_INVERT);
        $data['to'] = $mails;
        return $data;
    }
}

add_action('plugins_loaded', function() {
    include_once "table.php";
    new Blacklist();
});
