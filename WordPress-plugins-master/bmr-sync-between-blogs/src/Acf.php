<?php

namespace Bmr\Sync;

class Acf implements PostTypeSyncInterface
{
    public function get($limit = null, $offset = 0)
    {
        global $wpdb, $blog_id;

        $unSynced = $wpdb->get_results(
            "SELECT p.*
            FROM {$wpdb->base_prefix}posts p
            WHERE p.post_type = 'acf' AND p.post_status = 'publish'
            ORDER BY p.menu_order ASC, p.post_title ASC",
            ARRAY_A
        );
        $unSynced = $unSynced ?: [];

        foreach ($unSynced as $index => &$group) {
            $origMeta = Helper::getAcfMeta($group['ID'], 1);
            $group['status'] = __('Требуется синхронизация', 'bmr');

            if (!empty($origMeta['zwt_post_network'])) {
                // If group connected with current blog
                $postId  = false;
                $blogIds = wp_list_pluck($origMeta['zwt_post_network'], 'blog_id');
                $ind = array_search($blog_id, $blogIds);

                if ($ind !== false) {
                    $postId = $origMeta['zwt_post_network'][$ind]['post_id'];
                    $postId = get_post($postId) ? $postId : false;
                }

                if ($postId) {
                    $newMeta = Helper::getAcfMeta($postId, $blog_id);
                    $syncNeededMsg = $this->isSyncNeeded($origMeta, $newMeta);
                    if (!$syncNeededMsg) {
                        unset($unSynced[$index]);
                    } elseif (is_string($syncNeededMsg)) {
                        $group['status'] .= sprintf(' (%s)', $syncNeededMsg);
                    }
                }
            } else {
               $group['status'] = __('Группа полей не связана', 'bmr');
            }
        }
        $unSynced = array_slice($unSynced, $offset, $limit);
        return $unSynced;
    }

    public function count()
    {
        return count($this->get());
    }

    public function isSyncNeeded($origMeta, $newMeta)
    {
        foreach ($origMeta as $key => $data) {
            if (!isset($newMeta[$key])) {
                return true;
            } elseif (is_array($data)) {
                if ($key === 'fields') {
                    // compare fields
                    if (!$newMeta[$key]) {
                        return true;
                    }

                    $samePropCnt = count(array_intersect_key($data, $newMeta[$key]));
                    $origPropCnt = count($data);
                    $newPropCnt  = count($newMeta[$key]);

                    if ($origPropCnt !== $newPropCnt || $samePropCnt !== $origPropCnt) {
                        return __('отличается кол-во полей', 'bmr');
                    }

                    if (isset($data['choices']))

                    // Iterate over group fields
                    foreach ($data as $fieldKey => $field) {
                        $newField = $newMeta[$key][$fieldKey];

                        if (isset($field['choices'])) {
                            $diff1 = array_diff_key($field['choices'], $newField['choices']);
                            $diff2 = array_diff_key($newField['choices'], $field['choices']);

                            if ($diff1 || $diff2) {
                                return sprintf(__('отличаются списки в поле %s', 'bmr'), $field['label']);
                            }
                        }

                        if (isset($field['sub_fields'])) {

                        }

                        $diff = array_diff_assoc($field, $newField);
                        unset($diff['label'], $diff['key'], $diff['instructions']);

                        if ($diff) {
                           return sprintf(__('отличаются поля (%s)', 'bmr'), implode(',', $diff));
                        }
                    }

                } elseif ($key === 'rule') {
                    // compare rules
                    if (serialize($data) !== serialize($newMeta[$key])) {
                        return __('отличается местоположение', 'bmr');
                    }
                }
            } else {
                if ($data !== $newMeta[$key]) {
                    return sprintf(__('отличаются настройки `%s`', 'bmr'), $key);
                }
            }
        }
        return false;
    }

    private function compareSubFields($a, $b)
    {
        $aKeys = wp_list_pluck($a, 'name');
        $bKeys = wp_list_pluck($b, 'name');
        $a = array_combine($aKeys, $a);
        $b = array_combine($bKeys, $b);

        foreach ($a as $key => $prop) {
            $newField = $b[$key];
        }

    }

    public function sync($postId)
    {
        global $blog_id;

        // Get original group post
        switch_to_blog(1);
        $p = get_post($postId, ARRAY_A);
        restore_current_blog();

        if (!$p) {
            return new \WP_Error( 'sync', 'Can\'t get post!');
        }

        // Get acf group meta from original blog
        $origMeta = Helper::getAcfMeta($postId, 1);
        $newMeta  = [];
        $oldId    = $p['ID'];

        unset($p['ID']);

        if (!empty($origMeta['zwt_post_network'])) {
            // If group connected with current blog
            $postId  = false;
            $blogIds = wp_list_pluck($origMeta['zwt_post_network'], 'blog_id');
            $ind     = array_search($blog_id, $blogIds);

            if ($ind !== false) {
                $postId = $origMeta['zwt_post_network'][$ind]['post_id'];
                $postId = get_post($postId) ? $postId : false;
            }
            $postId && ($newMeta = Helper::getAcfMeta($postId, $blog_id));
        } else {
            // Otherwise creating new group
            $postId = wp_insert_post($p);
        }

        if ($postId) {
            $this->syncMeta($postId, $origMeta, $newMeta);

            if ($newMeta) {
                wp_cache_delete( 'field_groups', 'acf');
            } else {
                Helper::connectZantoPost($oldId, $postId);
            }
        } else {
            return new \WP_Error( 'sync', 'Can\'t sync this post');
        }
        return true;
    }

    public function syncMeta($postId, $origMeta, $newMeta)
    {
        foreach ($origMeta as $key => $data) {
            if (is_array($data)) {
                if ($key === 'fields') {
                    // compare fields
                    $fields = isset($newMeta[$key]) ? $newMeta[$key] : [];
                    $this->syncFields($postId, $data, $fields);
                } elseif ($key === 'rule') {
                    // compare rules
                    delete_post_meta($postId, $key);
                    foreach ($data as $rule) {
                        add_post_meta($postId, $key, $rule);
                    }
                }
            } else {
                update_post_meta($postId, $key, $data);
            }
        }
    }

    public function syncFields($postId, $field1, $field2)
    {
        $unusedFields = array_diff_key($field2, $field1);
        foreach($unusedFields as $field) {
            delete_post_meta($postId, $field['key']);
        }

        // TODO: make sync of repeater sub fields also (recursive?)
        foreach ($field1 as $fieldSlug => $fieldData) {
            $tmpField = [];

            if (isset($field2[$fieldSlug])) {
                // Field exists
                foreach($fieldData as $k => $v) {
                    // $k - field property, $v - property value
                    if (isset($field2[$fieldSlug][$k])) {
                        if (in_array($k, ['label', 'instructions'])) {
                            // leave existings prop. value
                            $tmpField[$k] = $field2[$fieldSlug][$k];
                        } elseif ($k === 'choices') {
                            // Check for new choices
                            $diff = array_diff_key($v, $field2[$fieldSlug][$k]);
                            // Check for removed choices
                            $toRemove = array_diff_key($field2[$fieldSlug][$k], $v);
                            $tmpField[$k] = array_merge($field2[$fieldSlug][$k], $diff);

                            foreach($toRemove as $key => $row) {
                                if (isset($tmpField[$k][$key])) {
                                    unset($tmpField[$k][$key]);
                                }
                            }
                      //} elseif ($k === 'sub_fields') {

                        } else {
                            $tmpField[$k] = $v;
                        }
                    } else {
                        $tmpField[$k] = $v;
                    }
                    update_post_meta($postId, $field2[$fieldSlug]['key'], $tmpField);
                }
            } else {
                add_post_meta($postId, $fieldData['key'], $fieldData);
            }
        }
    }


}