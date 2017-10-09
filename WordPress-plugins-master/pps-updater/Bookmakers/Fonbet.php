<?php

namespace PpsUpdater\Bookmakers;


class Fonbet extends \PpsUpdater\Bookmaker
{
    public function __construct()
    {
        $this->_bookmaker = 'Ф.О.Н.';
    }


    public function getData(&$error = '')
    {
        $ch = curl_init('http://bkfon.ru/ru/branches/data/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        $data = json_decode(curl_exec($ch), true);

        if (empty($data['branches'])) {
            $error = 'Какая-то ошибка! Не удалось получить данные.';

            return false;
        }

        foreach ($data['branches'] as $item) {
            $city = trim(str_ireplace(['г.', 'п.'], '', $item['city']));
            $schedule = [];

            if (mb_stripos($item['schedule'], 'круглосуточно') !== false) {
                $schedule = [
                    [
                        'day_of_week' => ['1', '2', '3', '4', '5', '6', '7'],
                        'time_start'  => '00:00',
                        'time_end'    => '00:00',
                    ]
                ];
            } else if (preg_match('#(\d\d:\d\d)-(\d\d:\d\d)#iu', $item['schedule'], $matches)) {
                $schedule = [
                    [
                        'day_of_week' => ['1', '2', '3', '4', '5', '6', '7'],
                        'time_start'  => $matches[1],
                        'time_end'    => $matches[2],
                    ]
                ];
            }

            $this->_items[] = [
                'city'     => $city,
                'address'  => trim(str_ireplace($city, '', $item['address']), ', '),
                'phone'    => $item['phones'],
                'site'     => '',
                'email'    => '',
                'coord'    => [
                    'lat' => $item['latitude'],
                    'lng' => $item['longitude'],
                ],
                'schedule' => $schedule,
            ];
        }

        return true;
    }
}