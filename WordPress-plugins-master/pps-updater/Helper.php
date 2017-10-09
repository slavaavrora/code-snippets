<?php

namespace PpsUpdater;


abstract class Helper
{
    public static function getPpsTypes()
    {
        return self::_getTaxonomyTerms('pps_types');
    }


    public static function getPpsServices()
    {
        return self::_getTaxonomyTerms('services');
    }


    private static function _getTaxonomyTerms($tax)
    {
        global $wpdb;

        $data = $wpdb->get_results('
            SELECT tt.term_taxonomy_id, t.name
            FROM ' . $wpdb->term_taxonomy . ' AS tt
            INNER JOIN ' . $wpdb->terms . ' AS t USING(term_id)
            WHERE tt.taxonomy = "' . esc_sql($tax) . '"
            ORDER BY t.name
        ');

        return wp_list_pluck($data, 'name', 'term_taxonomy_id');
    }
}