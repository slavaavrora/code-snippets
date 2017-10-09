<?php

namespace Bmr\Sync;

class Helper
{
    public static function attachmentUrlToPostId($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        global $wpdb;
        $uploadDir = trailingslashit(wp_upload_dir()['baseurl']);
        $file      = str_replace($uploadDir, '', $url);

        return $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %s",
            $file
        ));
    }

    public static function getAttachmentInfo($id)
    {
        $att  = get_post((int)$id, ARRAY_A);
        if (!$att) {
            return false;
        }
        unset($att['ID']);

        return [
            'attr' => $att,
            'file' => get_attached_file($id),
            'meta' => get_post_meta($id)
        ];
    }

    public static function postExists($post_title, $post_name)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT ID
            FROM {$wpdb->posts}
            WHERE post_title = %s OR post_name = %s AND post_status = 'publish'",
            $post_title, $post_name
        ));
    }

    public static function attachmentExists($name, $mimeType)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT ID
        FROM {$wpdb->posts}
        WHERE post_type = 'attachment' AND post_name = %s AND post_mime_type = %s",
            $name, $mimeType
        ));
    }

    public static function getAcfKeyByFieldName($field)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_value LIKE %s",
            '%' . $wpdb->esc_like($field) . '%'
        ));
    }

    public static function connectZantoPost($orig_id, $new_id)
    {
        $blog = get_current_blog_id();
        $postMeta = get_post_meta($orig_id, \ZWT_Base::PREFIX . 'post_network', true);
        $postMeta = !is_array($postMeta) ? [] : $postMeta;

        foreach ($postMeta as $key => $row) {
            if ($row['blog_id'] == 1) {
                unset($postMeta[$key]);
            }
        }
        $postMeta[] = [
            'blog_id' => 1,
            'post_id' => $orig_id
        ];
        update_post_meta($new_id, \ZWT_Base::PREFIX . 'post_network', $postMeta);

        switch_to_blog(1);
        $postMeta = get_post_meta($orig_id, \ZWT_Base::PREFIX . 'post_network', true);
        $postMeta = !is_array($postMeta) ? [] : $postMeta;

        foreach ($postMeta as $key => $row) {
            if ($row['blog_id'] == $blog) {
                unset($postMeta[$key]);
            }
        }
        $postMeta[] = [
            'blog_id' => $blog,
            'post_id' => $new_id
        ];
        update_post_meta($orig_id, \ZWT_Base::PREFIX . 'post_network', $postMeta);
        restore_current_blog();
    }

    public static function connectZantoTerm($orig_id, $new_id, $taxonomy)
    {
        $blog = get_current_blog_id();
        $taxMeta = get_option(\ZWT_Base::PREFIX . 'taxonomy_meta');
        $taxMeta[$taxonomy][$new_id][1] = $orig_id;
        update_option(\ZWT_Base::PREFIX . 'taxonomy_meta', $taxMeta);

        switch_to_blog(1);
        $taxMeta = get_option(\ZWT_Base::PREFIX . 'taxonomy_meta');
        $taxMeta[$taxonomy][$orig_id][$blog] = $new_id;
        update_option(\ZWT_Base::PREFIX . 'taxonomy_meta', $taxMeta);
        restore_current_blog();
    }

    /**
     * Get acf meta fields by post_id from specific blog
     *
     * @param int|string $post_id
     * @param string $blog_id
     * @return array
     */
    public static function getAcfMeta($post_id, $blog_id)
    {
        global $wpdb;
        $blog_id = $blog_id === 1 ? '' : $blog_id . '_';
        $data = [];

        $results = $wpdb->get_results(
            "SELECT pm.meta_key AS k, pm.meta_value AS v
        FROM {$wpdb->base_prefix}{$blog_id}postmeta pm
        WHERE
            pm.post_id = {$post_id}
            AND (pm.meta_key LIKE 'field_%' OR pm.meta_key IN ('position', 'layout', 'hide_on_screen', 'rule', 'zwt_post_network'))"
        );
        $results = is_array($results) ? $results : [];

        foreach ($results as $row) {
            $row->v = maybe_unserialize($row->v);
            if ($row->k === 'rule') {
                $data[$row->k][] = $row->v;
            } elseif (strpos($row->k, 'field_') !== false) {
                $data['fields'][$row->v['name']] = $row->v;
            } else {
                $data[$row->k] = $row->v;
            }
        }
        return $data;
    }

    public static function zantoGetPostId($originalPostId, $blogId)
    {
        switch_to_blog(1);
        $zwt = get_post_meta($originalPostId, \ZWT_Base::PREFIX . 'post_network', true);
        restore_current_blog();

        if ($zwt) {
            $blogs = wp_list_pluck($zwt, 'post_id', 'blog_id');
            return isset($blogs[$blogId]) ? $blogs[$blogId] : false;
        }
        return false;
    }

    public static function zantoGetTermId($originalTermId, $taxonomy, $blogId)
    {
        switch_to_blog(1);
        $zwt = get_option(\ZWT_Base::PREFIX . 'taxonomy_meta');
        restore_current_blog();

        if ($zwt) {
            return isset($zwt[$taxonomy][$originalTermId][$blogId]) ? $zwt[$taxonomy][$originalTermId][$blogId] : false;
        }
        return false;
    }
}