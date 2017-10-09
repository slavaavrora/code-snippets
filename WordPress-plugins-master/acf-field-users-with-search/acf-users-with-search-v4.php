<?php

class acf_field_users_with_search extends acf_field {
	const FILE_VERSION = '02101712';

	// vars
	var $settings, // will hold info such as dir / path
		$defaults; // will hold default field options
		
		
	/*
	*  __construct
	*
	*  Set name / label needed for actions / filters
	*
	*  @since	3.6
	*  @date	23/01/13
	*/
	
	public function __construct()
	{
		$this->name = 'users_with_search';
		$this->label = __('Users with search');
		$this->category = __('Relational', 'acf');
		$this->defaults = [];

		// do not delete!
    	parent::__construct();

    	// settings
		$this->settings = array(
			'path' => apply_filters('acf/helpers/get_path', __FILE__),
			'dir' => apply_filters('acf/helpers/get_dir', __FILE__),
			'version' => '1.0.0'
		);
	}

	/*
	*  create_options()
	*
	*  Create extra options for your field. This is rendered when editing a field.
	*  The value of $field['name'] can be used (like below) to save extra data to the $field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field	- an array holding all the field's data
	*/
	
	function create_options( $field )
	{
?>
        <tr class="field_option field_option_range_type field_option_<?= $this->name ?>">
            <td class="label">
                <label><?php _e('User role', 'acf'); ?></label>
            </td>
            <td>
                <?php
                $choices = ['all' => __('All', 'acf')];
                $editable_roles = get_editable_roles();

                foreach($editable_roles as $role => $details) {
                    $choices[$role] = translate_user_role($details['name']);
                }

                do_action('acf/create_field', [
                    'type'    => 'select',
                    'name'    => 'fields[' . $field['name'] . '][user_role]',
                    'choices' => $choices,
                    'value'   => $field['user_role'] ?: 'all',
                ]);
                ?>
            </td>
        </tr>
        <tr class="field_option field_option_range_type field_option_<?= $this->name ?>">
            <td class="label">
                <label><?php _e('Return value', 'acf'); ?></label>
            </td>
            <td>
                <?php
                do_action('acf/create_field', [
                    'type'    => 'radio',
                    'name'    => 'fields[' . $field['name'] . '][return_type]',
                    'choices' => [
                        'ids'     => __('Array of ids', 'acf'),
                        'objects' => __('Array of user objects', 'acf'),
                    ],
                    'value'   => $field['return_type'] ?: 'ids',
                    'layout'  => 'horizontal'
                ]);
                ?>
            </td>
        </tr>
<?php
	}
	
	
	/*
	*  create_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field - an array holding all the field's data
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/
	
	function create_field( $field )
	{
        global $wpdb;

        !is_array($field['value']) && ($field['value'] = []);
        $field['value'] = array_filter($field['value']);
?>
        <div class="acf-users-with-search">

            <div class="left">
                <div class="search-field">
                    <input placeholder="Поиск..." type="text" name="acf-users_with_search">
                </div>
                <ul class="users-list">
                    <?php
                    $role = isset($field['user_role']) && $field['user_role'] !== 'all' ? $field['user_role'] : '';
                    $allUsers = $wpdb->get_results('
                        SELECT
                            u.ID,
                            CONCAT(
                                IF(ufn.meta_value != "" AND uln.meta_value != "", CONCAT(ufn.meta_value, " ", uln.meta_value), u.user_login),
                                " (", u.user_email, ")"
                            ) AS name
                        FROM ' . $wpdb->users . ' AS u
                        INNER JOIN ' . $wpdb->usermeta . ' AS ufn
                            ON ufn.user_id = u.ID AND ufn.meta_key = "first_name"
                        INNER JOIN ' . $wpdb->usermeta . ' AS uln
                            ON uln.user_id = u.ID AND uln.meta_key = "last_name"
                        INNER JOIN ' . $wpdb->usermeta . ' AS uc
                            ON uc.user_id = u.ID AND uc.meta_key = "wp_capabilities"' . ($role ? ' AND uc.meta_value LIKE "%\"' . $role . '\"%"' : '') . '
                        ORDER BY name
                    ', ARRAY_A);
                    $allUsers = array_column($allUsers, 'name', 'ID');

                    foreach ($allUsers as $id => $name) :
                    ?>
                    <li data-id="<?= $id ?>"<?= in_array($id, $field['value']) ? ' class="selected"' : '' ?>>
                        <span class="title"><?= $name ?></span>
                        <span class="acf-button-add"></span>
                    </li>
                    <?php endforeach ?>
                </ul>
            </div>

            <div class="right">
                <ul>
                    <li class="template">
                        <span class="title"></span>
                        <span class="acf-button-remove"></span>
                        <input type="hidden" name="<?= $field['name'] ?>[]" value="0">
                    </li>
                    <?php foreach ($field['value'] as $id) :
                        if (!isset($allUsers[$id])) {
                            continue;
                        }
                    ?>
                    <li>
                        <span class="title"><?= $allUsers[$id] ?></span>
                        <span class="acf-button-remove"></span>
                        <input type="hidden" name="<?= $field['name'] ?>[]" value="<?= $id ?>">
                    </li>
                    <?php endforeach ?>
                </ul>
            </div>

        </div>
		<?php
	}
	
	
	/*
	*  input_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
	*  Use this action to add CSS + JavaScript to assist your create_field() action.
	*
	*  $info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/

	function input_admin_enqueue_scripts()
	{
		// register ACF scripts
		wp_register_script('acf-users-with-search-script', $this->settings['dir'] . '/js/main.js', [], self::FILE_VERSION);
		wp_register_style('acf-users-with-search-style', $this->settings['dir'] . '/css/style.css', [], self::FILE_VERSION);

		// scripts
		wp_enqueue_script(['acf-users-with-search-script']);

		// styles
		wp_enqueue_style(['acf-users-with-search-style']);
	}

	/*
	*  format_value_for_api()
	*
	*  This filter is applied to the $value after it is loaded from the db and before it is passed back to the API functions such as the_field
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value	- the value which was loaded from the database
	*  @param	$post_id - the $post_id from which the value was loaded
	*  @param	$field	- the field array holding all the field options
	*
	*  @return	$value	- the modified value
	*/
	
	function format_value_for_api( $value, $post_id, $field )
	{
        if ($value) {
            $value = is_array($value) ? array_filter($value) : $value;
            $field['return_type'] === 'objects' && ($value = get_users(['include' => $value]));
        }
        return $value;
	}

}


// create field
new acf_field_users_with_search();

?>
