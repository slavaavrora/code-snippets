<?php

namespace PpsUpdater;

final class UpdateData
{
    private $bookmaker,
            $bookmakerId,
            $scheduleAcfKey,
            $data,
            $type,
            $services,
            $country,
            $results = [
                'addedCities'  => 0,
                'addedItems'   => 0,
                'updatedItems' => 0,
                'deletedItems' => 0,
                'conflicts'    => 0,
            ];

    public function __construct(Bookmaker $bookmaker, $type, array $services, $country)
    {
        $this->bookmaker = $bookmaker->getName();
        $this->data = $bookmaker->getItems();
        $this->type = $type;
        $this->services = $services;
        $this->country = $country;

        global $wpdb;
        $this->bookmakerId = (int) $wpdb->get_var('
            SELECT ID
            FROM ' . $wpdb->posts . '
            WHERE post_type = "bookmaker" AND post_title = "' . esc_sql($this->bookmaker) . '"
            LIMIT 1
        ');
        $this->scheduleAcfKey = $wpdb->get_var('
            SELECT *
            FROM ' . $wpdb->postmeta . '
            WHERE meta_key = "_pps_schedule" AND meta_value LIKE "field\_%"
            LIMIT 1
        ');
    }

    public function update()
    {
        $dataByCity = [];
        foreach ($this->data as $id => $d) {
            !isset($dataByCity[$d['city']]) && $dataByCity[$d['city']] = [];
            $dataByCity[$d['city']][] = $id;
        }

        foreach ($dataByCity as $city => $ids) {
            if (!$this->_isCityExist($city)) {
                // city doesn't exist
                $newCity = wp_insert_term($city, 'locations', ['parent' => $this->country]);
                if (isset($newCity->error_data['term_exists'])) {
                    $cityId = $newCity->error_data['term_exists'];
                } else {
                    $this->results['addedCities']++;
                    $this->_createItems($ids, $newCity['term_id']);
                    continue;
                }
            }

            !isset($cityId) && ($cityId = $this->_getBookmakerCityIdByName($city));
            if (!$this->_isBookmakerCityExistById($cityId)) {
                // city exists but bookmaker's pps are not exist in city
                $this->_createItems($ids, $cityId);
                continue;
            }

        }

        return true;
    }

    public function getResults()
    {
        return $this->results;
    }

    /**
     * Returns array of pps' ids by city or empty array if city doens't exist.
     *
     * @param string $city
     *
     * @return array
     */
    private function _getPPSByCity($city)
    {
        static $data;
        $data === null && $data = $this->_getBookmakerPPS('city');

        return isset($data[$city]) ? $data[$city] : [];
    }

    /**
     * Returns PPS' ids for current bookmaker filtering it.
     *
     * If filtering by city returns array where key is city and value is array of ids.
     * Else returns array of all ids.
     *
     * @param string $filter Filter. Default - all
     *
     * @return array
     */
    private function _getBookmakerPPS($filter = 'all')
    {
        static $data;

        if ($data === null) {
            global $wpdb;

            $data = $wpdb->get_results('
                SELECT p.ID, t.name AS city
                FROM ' . $wpdb->posts . ' AS pb
                INNER JOIN ' . $wpdb->postmeta . ' AS pm
                    ON pm.meta_key = "pps_agency" AND pm.meta_value = pb.ID
                INNER JOIN ' . $wpdb->posts . ' AS p
                    ON p.ID = pm.post_id AND p.post_type = "pps" AND p.post_status = "publish"
                INNER JOIN ' . $wpdb->term_relationships . ' AS tr
                    ON tr.object_id = p.ID
                INNER JOIN ' . $wpdb->term_taxonomy . ' AS tt
                    ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = "locations"
                INNER JOIN ' . $wpdb->terms . ' AS t USING(term_id)
                WHERE pb.post_type = "bookmaker" AND pb.post_status = "publish" AND pb.post_title = "' . esc_sql($this->bookmaker) . '"
            ', ARRAY_A);
        }

        if ($filter === 'city') {
            $res = array_fill_keys(array_column($data, 'city'), []);

            foreach ($data as $d) {
                $res[$d['city']][] = $d['ID'];
            }

            return $res;
        }

        return array_column($data, 'ID');
    }

    /**
     * Checks city existence
     *
     * @param string $city
     *
     * @return bool
     */
    private function _isCityExist($city)
    {
        static $data;

        if ($data === null) {
            global $wpdb;

            $data = $wpdb->get_col('
                SELECT t.name
                FROM ' . $wpdb->term_taxonomy . ' AS tt
                INNER JOIN ' . $wpdb->terms . ' AS t USING(term_id)
                WHERE tt.taxonomy = "locations"
            ');
        }

        return in_array($city, $data);
    }

    private function _isBookmakerCityExistById($city)
    {
        return in_array($city, $this->_getBookmakerCities());
    }

    private function _getBookmakerCityIdByName($city)
    {
        $data = $this->_getBookmakerCities();

        return isset($data[$city]) ? $data[$city] : 0;
    }

    private function _getBookmakerCities()
    {
        static $data;

        if ($data === null) {
            global $wpdb;

            $data = $wpdb->get_results('
                SELECT DISTINCT t.term_id, t.name
                FROM ' . $wpdb->posts . ' AS p
                INNER JOIN ' . $wpdb->postmeta . ' AS pm
                    ON pm.post_id = p.ID AND pm.meta_key = "pps_agency" AND pm.meta_value = ' . (int) $this->bookmakerId . '
                INNER JOIN ' . $wpdb->term_relationships . ' AS tr
                    ON tr.object_id = p.ID
                INNER JOIN ' . $wpdb->term_taxonomy . ' AS tt
                    ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = "locations" AND tt.parent = ' . (int) $this->country . '
                INNER JOIN ' . $wpdb->terms . ' AS t USING (term_id)
                WHERE p.post_type = "pps" AND p.post_status = "publish"
            ', ARRAY_A);

            $data = array_column($data, 'term_id', 'name');
        }

        return $data;
    }


    private function _createItems($ids, $cityID)
    {
        foreach ((array) $ids as $id) {
            echo 'Create PPS - ' . $id;

            $data = $this->data[$id];

            $id = wp_insert_post([
                'post_title'  => $this->bookmaker . ', ' . $data['city'] . ', ' . $data['address'],
                'post_status' => 'publish',
                'post_type'   => 'pps',
                'tax_input'   => [
                    'locations' => [$cityID],
                    'pps_types' => [$this->type],
                    'services'  => $this->services,
                ],
            ]);

            if ($id) {
                $this->results['addedItems']++;
                update_field('pps_adress', $data['address'], $id);
                update_field('pps_agency', $this->bookmakerId, $id);
                update_field('pps_markers', [
                    'address' => $data['city'] . ', ' . $data['address'],
                    'lat'     => $data['coord']['lat'],
                    'lng'     => $data['coord']['lng'],
                ], $id);
                update_post_meta($id, '_pps_geo_lng', $data['coord']['lng']);
                update_post_meta($id, '_pps_geo_lat', $data['coord']['lat']);
                update_field($this->scheduleAcfKey, $data['schedule'], $id);
            }
        }
    }
    
}