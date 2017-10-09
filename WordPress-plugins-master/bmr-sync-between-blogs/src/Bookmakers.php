<?php

namespace Bmr\Sync;

class Bookmakers implements PostTypeSyncInterface
{
    /**
     * Post Type
     * @var string
     */
    private $postType;

    /**
     * Fields to sync
     * @var array
     */
    private $fields;

    public function __construct()
    {
        $this->postType = 'bookreviews';

        $this->fields = [
            'field_548185f9b8797' => 'field_548185f9b8797',  // about_bmr_general_logo
            'field_549d2c351e30c' => 'field_549d2c351e30c',  // about_bmr_general_white_logo
            'field_549c2f139369e' => 'field_549c2f139369e',  // about_bmr_rating
            'field_56029fac54254' => 'field_56029fac54254',  // reviews_product_quality_rating
            'field_5478575d97d3b' => 'field_5478575d97d3b',  // reviews_info_email
            'field_5478579997d3e' => 'field_5478579997d3e',  // reviews_digits_founding_date
            'field_5478580097d3f' => 'field_5478580097d3f',  // reviews_digits_established_in
            'field_5478583297d40' => 'field_5478583297d40',  // reviews_digits_number_of_betting_shops
            'field_5478585597d41' => 'field_5478585597d41',  // reviews_digits_financial_reports
            'field_54785bda8c79a' => 'field_54785bda8c79a',  // stock_price_ticker_symbol
            'field_5478615f8c79c' => 'field_5478615f8c79c',  // reviews_reliability_mediator
            'field_5478628f8c79e' => 'field_5478628f8c79e',  // reviews_reliability_altittude

            'field_560a71d4f5ce6' => 'field_560a7502861b8',  // reviews_own_software

            // Bookmaker stats
            'field_54d4b5d817e52' => 'field_54d4b5d817e52',  // reviews_stats_reliability
            'field_54d4b62417e53' => 'field_54d4b62417e53',  // reviews_stats_coefficients
            'field_54d4b64b17e54' => 'field_54d4b64b17e54',  // reviews_stats_support
            'field_54d4b67517e55' => 'field_54d4b67517e55',  // reviews_stats_payments_speed
            // Products And Services
            'field_5602a41f450f3' => 'field_5602a41f450f3',  // reviews_products
            'field_5602a442450f4' => 'field_5602a442450f4',  // reviews_services

            // Taxonomies
            'field_5602a0df5425a' => 'field_5602a0df5425a',  // reviews_info_founder
            'field_5602a1c45425b' => 'field_5602a1c45425b',  // reviews_info_director

            // Repeaters
            'field_5602a6e845115' => 'field_5602a6e845115',  // reviews_licences

            // Relationships
            'field_54786a683433b' => 'field_54786a683433b',  // reviews_softwares
        ];
    }

    public function get($limit = -1, $offset = 0)
    {
        $limit = $limit > 0 ? 'LIMIT ' . ($offset ? $offset . ',' . $limit : $limit) : '';
        global $wpdb, $blog_id;

        $unSynced = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*
            FROM wp_posts p
            LEFT JOIN wp_postmeta pm
        	    ON pm.post_id = p.ID AND pm.meta_key = 'zwt_post_network'
            WHERE p.post_type = %s AND p.post_status = 'publish'
                  AND (pm.meta_value IS NOT NULL AND pm.meta_value REGEXP 'blog_id\";(i:|s:[1-9]:\"){$blog_id}')
            $limit",
            $this->postType),
            ARRAY_A
        );

        $currentBlogId = $blog_id;
        switch_to_blog(1);
        foreach ($unSynced as $index => &$p) {
            $zwt              = get_post_meta($p['ID'], 'zwt_post_network', true);
            $blogs            = wp_list_pluck($zwt, 'post_id', 'blog_id');
            $p['ID_original'] = $p['ID'];
            $p['ID']          = (int)$blogs[$currentBlogId];
        }
        restore_current_blog();

        foreach ($unSynced as $index => $p) {
            $post = get_post($p['ID']);
            if (($post && $post->post_status !== 'publish') || !$post) {
                unset($unSynced[$index]);
            }
        }
        return is_array($unSynced) ? $unSynced : [];
    }

    public function count()
    {
        global $wpdb, $blog_id;

        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(p.ID)
            FROM wp_posts p
            LEFT JOIN wp_postmeta pm
        	    ON pm.post_id = p.ID AND pm.meta_key = 'zwt_post_network'
            WHERE p.post_type = %s AND p.post_status = 'publish'
                  AND (pm.meta_value IS NOT NULL AND pm.meta_value REGEXP 'blog_id\";(i:|s:[1-9]:\"){$blog_id}')",
            $this->postType
        ));
    }

    public function sync($postId)
    {
        $blogId = get_current_blog_id();

        switch_to_blog(BLOG_ID_BMR);
        $p = get_post($postId, ARRAY_A);

        if (!$p) {
            return new \WP_Error( 'sync', 'Can\'t get post!');
        }

        $remapMedia = [];
        $postFields = [];

        // Process meta fields
        foreach ($this->fields as $fieldKeyOriginal => $fieldKey) {
            $obj   = get_field_object($fieldKeyOriginal);
            $value = get_field($fieldKeyOriginal, $p['ID'], false);

            switch ($obj['type']) {
                case 'image':
                    !is_numeric($value) && ($value = Helper::attachmentUrlToPostId($value));

                    if ($value) {
                        $value = Helper::getAttachmentInfo($value);
                        $value && ($remapMedia[$fieldKey] = $value);
                    }
                    break;

                case 'taxonomy':
                    if (is_array($value)) {
                        $new = [];
                        foreach ($value as $termId) {
                            $tmp = Helper::zantoGetTermId($termId, 'person', $blogId);
                            $tmp && ($new[] = $tmp);
                        }
                        $value = $new;
                    } elseif ($value) {
                        $value = Helper::zantoGetTermId($value, 'person', $blogId);
                    }
                    $value && ($postFields[$fieldKey] = $value);
                    break;

                case 'repeater':
                    $value = get_field($fieldKeyOriginal, $p['ID'], true);
                    $value = is_array($value) && $fieldKey === 'field_5602a6e845115' ? $value : [];

                    foreach ($value as $index => $subfield) {
                        $term = $subfield['licence'];
                        $newId = Helper::zantoGetTermId($term->term_id, $term->taxonomy, $blogId);

                        if ($newId) {
                            switch_to_blog($blogId);
                            $term = get_term_by('id', $newId, $term->taxonomy);
                            restore_current_blog();

                            if ($term) {
                                $value[$index]['licence'] = $term->term_id;
                            } else {
                                unset($value[$index]);
                            }

                        } else {
                            unset($value[$index]);
                        }
                    }
                    $value && ($postFields[$fieldKey] = $value);
                    break;

                case 'relationship':
                    $new   = [];
                    $value = is_array($value) ? $value : [];

                    foreach ($value as $postId) {
                        $tmp = Helper::zantoGetPostId($postId, $blogId);
                        $tmp && ($new[] = $tmp);
                    }
                    $new && ($postFields[$fieldKey] = $new);
                    break;

                default:
                    $value && ($postFields[$fieldKey] = $value);
            }
        }

        // Process thumbnail
        if (has_post_thumbnail($p['ID'])) {
            $id = get_post_thumbnail_id($p['ID']);
            $id && $remapMedia['thumbnail'] = Helper::getAttachmentInfo($id);
        }

        $currentPostId = Helper::zantoGetPostId($p['ID'], $blogId);
        restore_current_blog();
        include_once(ABSPATH . 'wp-admin/includes/image.php');

        if (!$currentPostId) {
            return new \WP_Error( 'sync', 'Post that you are trying to sync, doesn\'t exists on current blog!');
        }

        $this->insertMeta($currentPostId, $postFields);
        $this->moveMedia($currentPostId, $remapMedia);
        return true;
    }

    public function moveMedia($postId, $data)
    {
        foreach ($data as $key => $info) {
            if (($key === 'thumbnail' && has_post_thumbnail($postId)) || get_field($key, $postId)) {
                continue;
            }
            $newId = $this->copyMediaPost($info, true);
            if (is_wp_error($newId) || !$newId) {
                continue;
            }
            if ($key !== 'thumbnail') {
                update_field($key, $newId, $postId);
            } else {
                set_post_thumbnail($postId, $newId);
            }
        }
    }

    /**
     * Inserts post meta
     *
     * @param $post_id
     * @param $meta
     */
    public function insertMeta($post_id, $meta)
    {
        foreach ($meta as $key => $value) {
            update_field($key, $value, $post_id);
        }
    }

    public function copyMediaPost($data, $fileToo = false)
    {
        // Check if attachment already exists
        $post_id = Helper::attachmentExists($data['attr']['post_name'], $data['attr']['post_mime_type']);
        if ($post_id) {
            return $post_id;
        }

        if (!$fileToo) {
            // Insert already existing attachment with different meta data
            $post_id = wp_insert_attachment($data['attr']);

            if (!$post_id) {
                return false;
            }
            wp_update_attachment_metadata($post_id, wp_generate_attachment_metadata($post_id, $data['file']));

        } else {
            // Copy attachment from another site
            $post_id = $this->fetchMedia($data['attr']['guid']);
        }
        return $post_id;
    }

    public function fetchMedia($mediaUrl)
    {
        $file_name = basename($mediaUrl);

        $upload = wp_upload_bits($file_name, 0, '');
        if ($upload['error']) {
            return new \WP_Error( 'upload_dir_error', $upload['error'] );
        }

        $headers = wp_get_http($mediaUrl, $upload['file']);

        if (!$headers) {
            @unlink($upload['file']);
            return new \WP_Error( 'import_file_error', __( 'Remote server did not respond', 'wordpress-importer') );
        }

        if ($headers['response'] != '200') {
            @unlink($upload['file']);
            return new \WP_Error(
+                sprintf(__('Remote server returned error response %1$d %2$s', 'wordpress-importer'),
                    esc_html($headers['response']), get_status_header_desc($headers['response']))
            );
        }

        $filesize = filesize($upload['file']);
        if (isset($headers['content-length']) && $filesize != $headers['content-length']) {
            @unlink($upload['file']);
            return new \WP_Error( 'import_file_error', __('Remote file is incorrect size', 'wordpress-importer') );

        }

        if (0 == $filesize) {
            @unlink($upload['file']);
            return new \WP_Error( 'import_file_error', __('Zero size file downloaded', 'wordpress-importer') );
        }

        $info = wp_check_filetype($upload['file']);

        if (!$info) {
            @unlink($upload['file']);
            return new \WP_Error('invalid_file_type_error', "Invalid file type");
        }

        $post_id = wp_insert_attachment([

            'guid'           => $upload['url'],
            'post_mime_type' => $info['type'],
            'post_title'     => preg_replace('/\.[^.]+$/', '', $file_name),
            'post_content'   => '',
            'post_status'    => 'inherit'

        ], $upload['file']);

        wp_update_attachment_metadata($post_id, wp_generate_attachment_metadata($post_id, $upload['file']));
        return $post_id;
    }

    public function setPostType($postType)
    {
        $this->postType = $postType;
    }

    public function getPostType()
    {
        return $this->postType;
    }
}