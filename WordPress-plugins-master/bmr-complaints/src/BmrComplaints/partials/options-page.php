<div class="wrap">
    <h2><?php echo esc_html(get_admin_page_title()) ?>
        <small>
        </small>
    </h2>

    <div class="row">
            <form action="options.php" method="POST">
                <?php settings_errors('bmr_settings'); ?>
                <?php settings_fields('bmr-settings'); ?>
                <?php do_settings_sections('bmr-settings'); ?>
                <?php submit_button(); ?>
            </form>
    </div>
</div>

