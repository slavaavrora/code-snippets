<?php

namespace Bmr\Sync;

class Posts implements PostTypeSyncInterface
{
    private $postType;

    public function __construct($postType)
    {
        $this->postType = $postType;
    }

    public function get($limit = -1, $offset = 0)
    {
        $limit = $limit > 0 ? 'LIMIT ' . ($offset ? $offset . ',' . $limit : $limit) : '';
        global $wpdb, $blog_id;

        $unSynced = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, p.ID as ID_original
            FROM wp_posts p
            LEFT JOIN wp_postmeta pm
        	    ON pm.post_id = p.ID AND pm.meta_key = 'zwt_post_network'
            WHERE p.post_type = %s AND p.post_status = 'publish'
                  AND (pm.meta_value IS NULL OR pm.meta_value NOT REGEXP 'blog_id\";(i:|s:[1-9]:\"){$blog_id}')
            $limit",
            $this->postType),
            ARRAY_A
        );

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
                  AND (pm.meta_value IS NULL OR pm.meta_value NOT REGEXP 'blog_id\";(i:|s:[1-9]:\"){$blog_id}')",
            $this->postType
        ));
    }

    public function sync($postId)
    {
        $data = [];

        \Base\Helpers\Main::switchToBlog(BLOG_ID_BMR);

        $p = get_post($postId, ARRAY_A);

        if (!$p) {
            return new \WP_Error( 'sync', 'Can\'t get post!');
        }

        $taxonomies = get_object_taxonomies($p);
        $terms      = get_the_terms($p['ID'], $taxonomies);
        $meta       = get_post_meta((int)$p['ID']);
        $tmp        = [];
        $remapMedia = [];
        $repeaters  = [];

        // Process meta fields
        foreach ($meta as $key => $field) {
            if (count($field) > 1) {
                $tmp[$key] = $field;
            } else {
                $value = reset($field);
                if (strpos($value, 'field_') === false) {
                    $tmp[$key] = $value;
                    $obj  = get_field_object($key, $p['ID'], false);

                    if ($obj['type'] === 'image') {
                        $remapMedia[$key] = Helper::getAttachmentInfo($value);
                        $repeaters[] = $key;
                    } elseif ($obj['type'] === 'repeater') {
                        $repeaters[] = $key;
                    }
                };
            }
        }
        $repeaters = array_unique($repeaters);
        $tmp['__repeaters'] = $repeaters;
        $meta = $tmp;
        $tmp  = [];

        // Process terms meta fields
        foreach ($terms as $term) {
            $catIcon   = get_field('category_icon', $term);
            $isPopular = get_field('term_popular', $term);

            if ($catIcon !== false) {
                $catIconId = is_numeric($catIcon) ? $catIcon : Helper::attachmentUrlToPostId($catIcon);
                $catIconId && $tmp['category_icon'] = Helper::getAttachmentInfo($catIconId);
            }
            $isPopular !== false && $tmp['term_popular'] = $isPopular;

            if ($tmp) {
                $term->meta = $tmp;
                $tmp = [];
            }
        }

        // Process thumbnail
        if (has_post_thumbnail($p['ID'])) {
            $id = get_post_thumbnail_id($p['ID']);
            $id && $remapMedia['thumbnail'] = Helper::getAttachmentInfo($id);
        }

        $data[$p['ID']] = [
            'post'     => $p,
            'meta'     => $meta,
            'terms'    => $terms,
            'to_remap' => $remapMedia
        ];

        \Base\Helpers\Main::switchToCurrentBlog();

        include_once(ABSPATH . 'wp-admin/includes/image.php');
        //set_time_limit(0);

        $oldId = $p['ID'];
        unset($p['ID']);
        $postId = Helper::postExists($p['post_title'], $p['post_name']);

        if ($postId) {
            return new \WP_Error( 'sync', 'Post that you are trying to sync, already exists!');
        }

        $postId = wp_insert_post($p, true);

        if (!is_wp_error($postId)) {
            $this->insertTerms($postId, $terms);
            $this->insertMeta($postId, $meta);
            $this->moveMedia($postId, $remapMedia);
            Helper::connectZantoPost($oldId, $postId);
        } else {
            return new $postId;
        }
        return true;
    }

    public function moveMedia($postId, $data)
    {
        foreach ($data as $key => $info) {
            $newId = $this->copyMediaPost($info, true);
            if (is_wp_error($newId) || !$newId) {
                continue;
            }
            if ($key !== 'thumbnail') {
                update_post_meta($postId, $key, $newId);
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
        $repeaters = array_key_exists('__repeaters', $meta) ? $meta['__repeaters'] : [];
        foreach ($meta as $key => $value) {
            update_post_meta($post_id, $key, $value);
            if (in_array($key, $repeaters, true)) {
                $id = Helper::getAcfKeyByFieldName($key);
                $id && update_post_meta($post_id, '_' . $key, $id);
            }
        }
    }

    /**
     * Inserts term acf meta fields
     *
     * @param $term_id
     * @param $taxonomy
     * @param $meta
     */
    public function insertTermMeta($term_id, $taxonomy, $meta)
    {
        $term = get_term($term_id, $taxonomy);

        if (!is_wp_error($term) && $term) {
            foreach ($meta as $key => $data) {
                if ($key === 'category_icon') {
                    $newId = $this->copyMediaPost($data, true);
                    if (is_wp_error($newId) || !$newId) {
                        continue;
                    }
                    $data = $newId;
                }
                update_field($key, $data, $term);
            }
        }
    }

    /**
     * Inserts post terms
     *
     * @param $post_id
     * @param $terms
     */
    public function insertTerms($post_id, $terms)
    {
        $taxes = [];
        foreach ($terms as $term) {
            $args = [
                'slug'        => $term->slug,
                'description' => $term->description,
                'parent'      => $term->parent
            ];

            $termData = term_exists($term->slug, $term->taxonomy);
            if (!$termData) {
                $termData = wp_insert_term($term->name, $term->taxonomy, $args);

                if (!is_wp_error($termData)) {
                    Helper::connectZantoTerm($term->term_id, $termData['term_id'], $term->taxonomy);
                    property_exists($term, 'meta')
                    && $this->insertTermMeta($termData['term_id'], $term->taxonomy, $term->meta);
                }
            }
            !is_wp_error($termData) && $taxes[$term->taxonomy][] = $termData;
        }
        foreach($taxes as $t => $tt) {
            $termIds = wp_list_pluck($tt, 'term_id');
            $termIds = array_map('intval', $termIds);
            wp_set_object_terms($post_id, $termIds, $t);
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
            return new \WP_Error( 'import_file_error', __('Remote server did not respond', 'wordpress-importer') );
        }

        if ($headers['response'] != '200') {
            @unlink($upload['file']);
            return new \WP_Error(
                'import_file_error',
                sprintf(__('Remote server returned error response %1$d %2$s', 'wordpress-importer'),
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