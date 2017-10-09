<?php

class acf_field_table_fields extends acf_field {
	const FILE_VERSION = '09091911';

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
		$this->name = 'table_fields';
		$this->label = __('Table of fields');
		$this->category = __('Layout', 'acf');
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
        $key = $field['name'];

        // Create Field Options HTML
        ?>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label><?php _e('Columns', 'acf'); ?></label>
                <p class="description"><?php _e('key : title', 'acf'); ?></p>
            </td>
            <td>
                <?php

                do_action('acf/create_field', array(
                    'type'		=>	'textarea',
                    'name'		=>	'fields[' . $key . '][columns]',
                    'value'		=>	$field['columns'],
                    'layout'	=>	'horizontal',
                    'choices'	=>	array(
                        'thumbnail' => __('Thumbnail'),
                        'something_else' => __('Something Else'),
                    )
                ));

                ?>
            </td>
        </tr>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label><?php _e('Rows', 'acf'); ?></label>
                <p class="description"><?php _e('key : title', 'acf'); ?></p>
            </td>
            <td>
                <?php

                do_action('acf/create_field', array(
                    'type'		=>	'textarea',
                    'name'		=>	'fields[' . $key . '][rows]',
                    'value'		=>	$field['rows'],
                    'layout'	=>	'horizontal',
                    'choices'	=>	array(
                        'thumbnail' => __('Thumbnail'),
                        'something_else' => __('Something Else'),
                    )
                ));

                ?>
            </td>
        </tr>
        <?php

    }


    private function _parseData($data)
    {
        $result = [];

        preg_match_all('#(\w+)\s*:\s*([^\r\n]+)#', $data, $matches, PREG_SET_ORDER);
        foreach ($matches as $item) {
            $result[$item[1]] = $item[2];
        }

        return $result;
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
        $columns = $this->_parseData($field['columns']);
        $rows = $this->_parseData($field['rows']);

        ?>
        <input type="hidden" name="<?= $field['name'] ?>[fakeCheckboxForAcfFilters]" value="1">
        <div class="acf-table-fields">
            <table>
                <tr class="top">
                    <th></th><th><?= implode('</th><th>', $columns) ?></th>
                </tr>
                <?php foreach ($rows as $rowKey => $rowTitle) : ?>
                <tr>
                    <th><?= $rowTitle ?></th>
                    <?php foreach ($columns as $colKey => $colTitle) :
                        $fieldName = $colKey . '_' . $rowKey;
                    ?>
                    <td><input type="checkbox" name="<?= $field['name'] ?>[<?= $fieldName ?>]"<?= isset($field['value'][$fieldName]) ? ' checked' : '' ?>></td>
                    <?php endforeach ?>
                </tr>
                <?php endforeach ?>
            </table>
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
        wp_register_style('acf-users-with-search', $this->settings['dir'] . 'css/style.css', [], self::FILE_VERSION);
        wp_enqueue_style(['acf-users-with-search']);
    }


    /*
    *  update_value()
    *
    *  This filter is applied to the $value before it is updated in the db
    *
    *  @type	filter
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$value - the value which will be saved in the database
    *  @param	$post_id - the $post_id of which the value will be saved
    *  @param	$field - the field array holding all the field options
    *
    *  @return	$value - the modified value
    */
    function update_value( $value, $post_id, $field )
    {
        $columns = $this->_parseData($field['columns']);
        $rows = $this->_parseData($field['rows']);

        foreach ($rows as $rowKey => $rowTitle) {
            foreach ($columns as $colKey => $colTitle) {
                $fieldName = $colKey . '_' . $rowKey;
                $metaName = str_replace('_', '', $colKey) . '_' . str_replace('_', '', $rowKey);
                update_post_meta($post_id, '_' . $field['name'] . '_' . $metaName, (int) isset($value[$fieldName]));
            }
        }

        return $value;
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
        unset($value['fakeCheckboxForAcfFilters']);
        return [
            'columns'  => $this->_parseData($field['columns']),
            'rows'     => $this->_parseData($field['rows']),
            'selected' => is_array($value) ? array_keys($value) : [],
        ];
    }
}


// create field
new acf_field_table_fields();

?>
