<?php
namespace Bmr\Assistant;

class Helper
{
    /**
     * Склонение существительных с числительными
     *
     * @param int $n число
     * @param array $forms формы склонения (прим.  ['Жалоба', 'Жалобы', 'Жалоб'])
     * @return string mixed
     */
    public static function pluralForm($n, $forms) {
        return $n % 10 == 1 && $n % 100 != 11 ? $forms[0] : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? $forms[1] : $forms[2]);
    }

    public static function getStats($user_id = 0, $quiz_id = 0)
    {
        global $wpdb;

        if ($user_id && $quiz_id) {
            $stats = $wpdb->get_var($wpdb->prepare(
                "SELECT result
                FROM {$wpdb->quiz}
                WHERE user_id = %d AND quiz_id = %d",
                $user_id, $quiz_id
            ));
            $stats = maybe_unserialize($stats);
        } else {
            $stats = $wpdb->get_results($wpdb->prepare(
                "SELECT user_id, result
                FROM {$wpdb->quiz}
                WHERE quiz_id = %d",
                $quiz_id
            ));
            $stats = $stats === null ? [] : $stats;
            $tmp = [];
            foreach ($stats as $stat) {
                $tmp[$stat->user_id] = maybe_unserialize($stat->result);
            }
            $stats = $tmp;
        }
        return $stats;
    }


    public static function updateStats($user_id, $quiz_id, $data)
    {
        global $wpdb;
        unset($data['action']);
        $len  = count($data);
        $data = serialize($data);

        if ($len && !self::isStatsExists($user_id, $quiz_id)) {
            $wpdb->insert(
                $wpdb->quiz,
                [
                    'quiz_id'    => $quiz_id,
                    'user_id'    => $user_id,
                    'result'     => $data,
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s']
            );
        } elseif ($len) {
            $wpdb->update(
                $wpdb->quiz,
                [
                    'result' => $data,
                    'created_at' => current_time('mysql')
                ],
                [
                    'quiz_id' => $quiz_id,
                    'user_id' => $user_id,
                ],
                ['%s', '%s'],
                ['%d', '%d']
            );
        } else {
            $wpdb->query($wpdb->prepare(
                "DELETE
                FROM $wpdb->quiz
                WHERE user_id = %d AND quiz_id = %d",
                $user_id, $quiz_id
            ));
        }
    }

    public static function isStatsExists($user_id, $quiz_id)
    {
        global $wpdb;
        return (bool)$wpdb->get_var($wpdb->prepare(
            "SELECT user_id
            FROM {$wpdb->quiz}
            WHERE user_id = %d AND quiz_id = %d",
            $user_id, $quiz_id
        ));
    }


}