<?php
namespace Bmr\Comments;

/**
 * Class Backend
 *
 * @package Bmr\Comments
 */
class Backend
{
    public static function fieldInput($args)
    {
        $name        = esc_attr($args['name']);
        $value       = esc_attr($args['value']);
        $type        = isset($args['type']) ? esc_attr($args['type']) : 'text';
        $description = isset($args['description']) ? $args['description'] : '';

        echo "<input type='{$type}' name='{$name}' value='{$value}' size='65' />";
        $description && print "<p><label for='{$name}'>{$description}</label></p>";
    }

    public static function fieldTextarea($args)
    {
        $name        = esc_attr($args['name']);
        $value       = esc_textarea($args['value']);
        $description = isset($args['description']) ? $args['description'] : '';

        $description && print "<p><label for='{$name}'>{$description}</label></p>";
        echo "<textarea id='{$name}' name='{$name}' rows='10' cols='50' class='large-text code'>{$value}</textarea>";
    }
}
