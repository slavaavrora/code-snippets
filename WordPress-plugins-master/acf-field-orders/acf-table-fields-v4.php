<?php

class acf_field_orders extends acf_field {
	const FILE_VERSION = '24111720';

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
		$this->name = 'field_orders';
		$this->label = __('Field orders');
		$this->category = __('Layout', 'acf');
		$this->defaults = [43];

		// do not delete!
    	parent::__construct();

    	// settings
		$this->settings = array(
			'path'    => apply_filters('acf/helpers/get_path', __FILE__),
			'dir'     => apply_filters('acf/helpers/get_dir', __FILE__),
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
                <label><?php _e('List of blocks', 'acf'); ?></label>
                <p class="description"><?php _e('key : label', 'acf'); ?></p>
            </td>
            <td>
                <?php

                do_action('acf/create_field', array(
                    'type'		=>	'textarea',
                    'name'		=>	'fields[' . $key . '][blocks]',
                    'value'		=>	$field['blocks'],
                    'layout'	=>	'horizontal',
                    'choices'	=>	array(
                        'thumbnail'      => __('Thumbnail'),
                        'something_else' => __('Something Else'),
                    )
                ));

                ?>
            </td>
        </tr>
        <?php

    }


    private function _getBlocks($data)
    {
        $blocks = [];

        foreach (preg_split('#[\r\n]+#', $data) as $b) {
            $parts = explode(' : ', $b, 2);
            !empty($parts[0]) && !empty($parts[1]) && ($blocks[$parts[0]] = $parts[1]);
        }

        return $blocks;
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
        $allBlocks = $this->_getBlocks($field['blocks']);
        $blocks = [];

        foreach (is_array($field['value']) ? $field['value'] : [] as $k) {
            !empty($allBlocks[$k]) && ($blocks[$k] = $allBlocks[$k]);
        }
        $blocks += $allBlocks;

        ?>
        <div class="acf-field-orders">
            <div class="container">
                <?php foreach ($blocks as $key => $label) : ?>
                <div class="item">
                    <input type="hidden" name="<?= $field['name'] ?>[]" value="<?= $key ?>">
                    <?= $label ?>
                </div>
                <?php endforeach ?>

                <div class="fake-item hide"></div>
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
        wp_enqueue_style('acf-field-orders', $this->settings['dir'] . 'css/style.css', [], self::FILE_VERSION);
        wp_enqueue_script('acf-field-orders', $this->settings['dir'] . 'js/script.js', [], self::FILE_VERSION);
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
        $allBlocks = array_keys($this->_getBlocks($field['blocks']));
        !is_array($value) && ($value = []);

        return array_intersect($value, $allBlocks) + $allBlocks;
    }

}


// create field
new acf_field_orders();

?>
