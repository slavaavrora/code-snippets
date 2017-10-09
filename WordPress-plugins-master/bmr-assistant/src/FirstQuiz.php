<?php


namespace Bmr\Assistant;


class FirstQuiz implements QuizInterface
{
    /**
     * AJAX Actions
     * @var array
     */
    public $actions;

    /**
     * Partials
     * @var array
     */
    protected $partials;

    /**
     * Found matches
     * @var array
     */
    private $matches;

    /**
     * @var array
     */
    private $criteria;

    /**
     * @var array
     */
    private $data;

    /**
     * @var bool
     */
    private $isNextAllowed;

    /**
     * @var int
     */
    private $user;

    /**
     * @var int
     */
    const QUIZ_ID = 1;

    public function __construct()
    {
        $this->partials = [
            'products'  => [
                'arrow_title' => __('Продукт', 'bmr'),
                'heading'     => __('Какие продукты вас интересуют?', 'bmr')
            ],
            'devices'  => [
                'arrow_title' => __('Устройства', 'bmr'),
                'heading'     => __('Какими устройствами вы пользуетесь для ставок?', 'bmr')
            ],
            'player'   => [
                'arrow_title' => __('Игрок', 'bmr'),
                'heading'     => __('Опишите себя как игрока', 'bmr')
            ],
            'time'     => [
                'arrow_title' => __('Время', 'bmr'),
                'heading'     => __('Когда вы обычно делаете ставки?', 'bmr')
            ],
            'finances' => [
                'arrow_title' => __('Финансы', 'bmr'),
                'heading'     => __('Финансы', 'bmr')
            ],
            'payment'  => [
                'arrow_title' => __('Счет', 'bmr'),
                'heading'     => __('Способы пополнения счета', 'bmr')
            ],
            'language' => [
                'arrow_title' => __('Языки', 'bmr'),
                'heading'     => __('Языки', 'bmr')
            ],
            'criteria' => [
                'arrow_title' => __('Критерии', 'bmr'),
                'heading'     => __('Укажите важность этих критериев при выборе конторы', 'bmr')
            ],
            'results'  => [
                'arrow_title' => __('Результаты', 'bmr'),
                'heading'     => __('Подходящие Вам букмекерские конторы:', 'bmr')
            ],
        ];

        $this->criteria      = [];
        $this->isNextAllowed = true;
        $this->matches       = [];
        $this->data          = [];
        $this->actions       = [
            'get_assistant_partial' => [$this, 'getPartialAction'],
            'query'                 => [$this, 'query'],
        ];

        foreach ($this->actions as $action => $handler) {
            add_action('wp_ajax_' . $action, $handler);
            add_action('wp_ajax_nopriv_' . $action, $handler);
        }

        $this->user = get_current_user_id();
    }

    public function query()
    {
        $this->data = esc_sql($_POST);

        if (!empty($this->data['results'])) {
            $this->user && Helper::updateStats($this->user, self::QUIZ_ID, $this->data);
            die("{}");
        }

        $this
            ->filterByProducts()
            ->filterByDevices()
            ->filterByPlayer()
            ->filterByTime()
            ->filterByFinances()
            ->filterByPayment()
            ->filterByLanguages()
            ->filterByCriteria();

        $cnt = [
            'full'    => 0,
            'partial' => 0
        ];

        if ($this->matches) {
            foreach ($this->matches as $index => $match) {
                $match->percent = ceil((count($match->criteria) / count($this->criteria)) * 100);

                if ($match->percent < 1) {
                    unset($this->matches[$index]);
                    continue;
                }
                if ($match->percent == 100) {
                    $cnt['full']++;
                } else {
                    $cnt['partial']++;
                }
            }
        }
        $this->matches['criteria'] = $this->getCriteriaData($this->criteria);
        Session::set('assistant_matches', $this->matches);
        die(json_encode($cnt));
    }

    protected function filterByProducts()
    {
        /** @var array $products */
        $products = isset($this->data['products']) ? $this->data['products'] : false;

        if (!$this->isNextAllowed || !$products) {
            $this->isNextAllowed = false;
            return $this;
        }

        $this->addCriteria($products);
        $assoc = [
            'reviews_products_and_features_sport'             => '_reviews_products_%_sport',
            'reviews_products_and_features_casino'            => '_reviews_products_%_casino',
            'reviews_products_and_features_live_casino'       => '_reviews_products_%_livecasino',
            'reviews_products_and_features_poker'             => '_reviews_products_%_poker',
            'reviews_products_and_features_games'             => '_reviews_products_%_games',
            'reviews_products_and_features_financial_betting' => '_reviews_products_%_financialbetting',
            'reviews_products_and_features_virtual_sport'     => '_reviews_products_%_virtualsport',
            'reviews_products_and_features_bingo'             => '_reviews_products_%_bingo',
            'reviews_products_and_features_cybersport'        => '_reviews_products_%_cybersport',
            'reviews_products_and_features_totalizator'       => '_reviews_products_%_totalizator',
        ];
        $regRepl = "#_reviews_products_(site|mobsite|osx|iostablet|iosphone|android|windows|windowsphone)(ru)?_#";

        global $wpdb;
        // Prepare products where query
        $cnt      = count($products);
        $where    = array_fill(0, $cnt, 'pm.meta_key LIKE %s');
        $where    = implode(' OR ', $where);
        $where    = "AND ($where)";

        $this->data['products'] = $tmp = [];
        foreach ($products as $product) {
            $this->data['products'][] = $assoc[$product];
            $tmp[] = str_replace('_', '\_', $assoc[$product]);
        }
        $products = $tmp;
        $where    = $wpdb->prepare($where, $products);
//
//        $results = $wpdb->get_results(
//            "SELECT p.ID, pm.meta_key as product
//            FROM {$wpdb->posts} p
//            INNER JOIN {$wpdb->postmeta} pm
//            ON pm.post_id = p.ID $where AND pm.meta_value = 1
//            WHERE
//                p.post_status = 'publish'
//                AND p.post_type = 'bookreviews'
//            "
//        );
//
//        $results2 =

        $results = $wpdb->get_results(
            "SELECT p.ID, pm.meta_key as product
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm
            ON pm.post_id = p.ID $where AND pm.meta_value = 1
            LEFT JOIN {$wpdb->postmeta} pm1
            ON pm1.post_id = p.ID AND pm1.meta_key = 'about_bmr_rating'
            WHERE
                p.post_status = 'publish'
                AND p.post_type = 'bookreviews'
                AND CAST(IFNULL(pm1.meta_value, 0) AS SIGNED) > 2
            ORDER BY p.ID"
        );



        $results = !is_array($results) ? [] : $results;
        $tmp     = [];
        $lastId  = 0;

        foreach ($results as $row) {
            if (strpos($row->product, 'ru') !== false) {
                continue;
            }
            if ($row->ID != $lastId && $lastId !== 0) {
                $filtered = implode(',', $tmp[$lastId]);
                $filtered = preg_replace($regRepl, '', $filtered);
                $filtered = explode(',', $filtered);
                $filtered = array_unique($filtered);
                $this->matches[$lastId] = new \stdClass();
                $this->matches[$lastId]->ID = $lastId;
                $this->matches[$lastId]->percent = ceil((count($filtered) / $cnt) * 100);

                $filtered = array_map(function($v) {
                    return 'products=' . $v;
                }, $filtered);
                $this->matches[$lastId]->criteria = $filtered;
            }
            $tmp[$row->ID][] = $row->product;
            $lastId = $row->ID;
        }

        if ($results) {
            $filtered = implode(',', $tmp[$lastId]);
            $filtered = preg_replace($regRepl, '', $filtered);
            $filtered = explode(',', $filtered);
            $filtered = array_unique($filtered);
            $this->matches[$lastId] = new \stdClass();
            $this->matches[$lastId]->ID = $lastId;
            $this->matches[$lastId]->percent = ceil((count($filtered) / $cnt) * 100);

            $filtered = array_map(function($v) {
                return 'products=' . $v;
            }, $filtered);
            $this->matches[$lastId]->criteria = $filtered;
        }
        return $this;
    }

    protected function filterByDevices()
    {
        /** @var array $products */
        $products = isset($this->data['products']) ? $this->data['products'] : false;
        /** @var array $devices */
        $devices = isset($this->data['devices']) ? $this->data['devices'] : false;
        $ids     = wp_list_pluck($this->matches, 'ID');

        if (!$devices || !$ids) {
            return $this;
        }

        global $wpdb;
        $where    = [];
        $this->addCriteria($this->filterDevices($devices));

        $devices = array_map(function($v) {
            return str_replace(['_tablet', '_phone'], '', $v);
        }, $devices);
        $devices = array_unique($devices);

        foreach ($devices as $device) {
            foreach ($products as $product) {
                $where[] = 'pm.meta_key = "' . str_replace('%', $device, $product) . '"';
            }
        }
        unset($device, $product);

        $cnt = count($devices);
        // Prepare post ids
        $ids = implode(',', $ids);
        // Prepeare where query
        $where = implode(' OR ', $where);
        $where = "AND ($where)";

        $results = $wpdb->get_results(
            "SELECT p.ID, pm.meta_key AS device
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm
            ON pm.post_id = p.ID $where AND pm.meta_value = 1
            WHERE p.ID IN ($ids)
            ORDER BY p.ID"
        );
        $results = $results ?: [];
        $tmp     = [];

        // Group By ID
        foreach ($results as $row) {
            $tmp[$row->ID][] = $row->device;
        }
        $results = $tmp;
        unset($tmp, $row);

        foreach ($this->matches as $id => $match) {
            if (!isset($results[$id])) {
                unset($this->matches[$id]);
                continue;
            }

            $filtered = implode(',', $results[$id]);
            preg_match_all('#products_(.+?)_#', $filtered, $devices);
            preg_match_all('#products_.+?_([^,]+)#', $filtered, $products);

            if (!$devices[1] || !$products[1]) {
                unset($this->matches[$id]);
                continue;
            }
            $devices = array_unique($devices[1]);
            $criteria = $this->filterDevices($devices);

            $products = array_unique($products[1]);
            $products = array_map(function($v) {
                return 'products=' . $v;
            }, $products);
            $criteria = array_merge($criteria, $products);

            $match->criteria = $criteria;
        }
        return $this;
    }

    public function filterByPlayer()
    {
        /** @var string $player */

        $playerType   = isset($this->data['player']['type']) ? $this->data['player']['type'] : 'beginner';
        $playerInfo   = isset($this->data['player']['extra']) ? $this->data['player']['extra'] : false;
        $highs        = isset($this->data['finances']['highs']) ? $this->data['finances']['highs'] : 'no';
        $ids          = wp_list_pluck($this->matches, 'ID');
        $incList      = ['vilochnik', 'koridorist', 'valuyschik', 'knopochnik', 'bonushunter'];

        if (!$ids || empty($this->data['player'])) {
            return $this;
        }

        $this->addCriteria(['player=' . $playerType]);

        // Если выбран Профессионал и тип игрока один из указанных ниже, то ищем те обзоры в которых отношение
        // к спекулянтам выставлено в "Дружелюбное"
        if (/*$highs === 'yes' || */($playerType === 'expert' && !in_array($playerInfo, $incList, true))) {
            foreach ($this->matches as $id => $match) {
                $match->criteria = array_merge($match->criteria, ['player=' . $playerType]);
            }
            return $this;
        }

        global $wpdb;
        // Prepare post ids
        $ids      = implode(',', $ids);
        $operator = in_array($playerType, ['amateur', 'beginner'], true) ? '!=' : '=';

        $results = $wpdb->get_col(
            "SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm
            ON pm.post_id = p.ID
            WHERE p.ID IN ($ids) AND pm.meta_key = 'reviews_reliability_altittude' AND pm.meta_value $operator '1'
            GROUP BY p.ID"
        );

        foreach ($this->matches as $id => $match) {
            if (in_array($id, $results)) {
                $match->criteria = array_merge($match->criteria, ['player=' . $playerType]);
            }
        }
        return $this;
    }

    public function filterByTime()
    {
        return $this;
    }

    public function filterByFinances()
    {
        $highs      = isset($this->data['finances']['highs']) ? $this->data['finances']['highs'] : 'no';
        $avg        = isset($this->data['finances']['avg']) ? $this->data['finances']['avg'] : 0;
        $currencies = isset($this->data['finances']['currency']) ? $this->data['finances']['currency'] : [];
        $ids        = wp_list_pluck($this->matches, 'ID');
        $criteria   = [];

        if (!$ids || empty($this->data['finances'])) {
            return $this;
        }

        // Fill selected criteria
        $criteriaCurrencies = array_map(function ($v) {
            return 'finances=supported_currencies_' . $v;
        }, $currencies);
        $criteriaAvg        = 'finances=avg-' . $avg;
//        $criteriaHighs      = 'finances=highs-' . $highs;
//        $criteria[]         = $criteriaHighs;
        $criteria[]         = $criteriaAvg;
        $criteria           = array_merge($criteria, $criteriaCurrencies);

        $this->addCriteria($criteria);

        global $wpdb;
        // Prepare post ids
        $ids      = implode(',', $ids);
        $term_ids = implode(',', $currencies);
        $where    = '';
        $joins    = '';
        $having   = '';

        // Если "Вам урезали максимумы" выставлено в Да, то ищем те обзоры в которых отноешние
        // к спекулянтам выставлено в "Дружелюбное"

        /*$joins .= " INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID";
        $val = $highs !== 'yes' ? '1' : '2';
        $where .= " AND pm.meta_key = 'reviews_reliability_altittude' AND pm.meta_value = {$val}";

        $results = $wpdb->get_col(
            "SELECT p.ID
            FROM {$wpdb->posts} p
            $joins
            WHERE p.ID IN ($ids) $where
            GROUP BY p.ID
            $having"
        );

        foreach ($this->matches as $id => $match) {
            if (in_array($id, $results)) {
                $match->criteria = array_merge($match->criteria, [$criteriaHighs]);
                if (!in_array($criteriaAvg, $match->criteria, true)) {
                    $match->criteria = array_merge($match->criteria, [$criteriaAvg]);
                }
            }
        }*/

        foreach ($this->matches as $id => $match) {
            $match->criteria = array_merge($match->criteria, [$criteriaAvg]);
        }

        if ($term_ids) {
            $where = $joins = '';
            $joins .=
                   " INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
                     INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id";
            $where .= " AND tt.term_id IN ($term_ids)";
            $having = 'HAVING COUNT(tt.term_id) = ' . count($currencies);

            $results = $wpdb->get_col(
                "SELECT p.ID
            FROM {$wpdb->posts} p
            $joins
            WHERE p.ID IN ($ids) $where
            GROUP BY p.ID
            $having"
            );

            foreach ($this->matches as $id => $match) {
                if (in_array($id, $results)) {
                    $match->criteria = array_merge($match->criteria, $criteriaCurrencies);
                    if (!in_array($criteriaAvg, $match->criteria, true)) {
                        $match->criteria = array_merge($match->criteria, [$criteriaAvg]);
                    }
                }
            }
        }
        return $this;
    }

    public function filterByPayment()
    {
        $payments = isset($this->data['payment']) ? $this->data['payment'] : [];
        $ids      = wp_list_pluck($this->matches, 'ID');

        if (!$ids || !$payments) {
            return $this;
        }

        $criteria = array_map(function($v) {
            return 'payment=supported_payments_' . $v;
        }, $payments);

        $this->addCriteria($criteria);

        global $wpdb;
        // Prepare post ids
        $ids      = implode(',', $ids);
        $term_ids = implode(',', $payments);

        $results = $wpdb->get_results(
            "SELECT p.ID, tt.term_id as method
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->term_relationships} tr
                ON tr.object_id = p.ID
            INNER JOIN {$wpdb->term_taxonomy} tt
                ON tt.term_taxonomy_id = tr.term_taxonomy_id
            WHERE p.ID IN ($ids) AND tt.term_id IN ($term_ids)"
        );
        $results = $results ?: [];

        // Group By ID
        $tmp = [];
        foreach ($results as $row) {
            $tmp[$row->ID][] = $row->method;
        }
        $results = $tmp;
        unset($tmp, $row);

        foreach ($this->matches as $id => $match) {
            if (isset($results[$id])) {
                $criteria = array_map(function ($v) {
                    return 'payment=supported_payments_' . $v;
                }, $results[$id]);
                $match->criteria = array_merge($match->criteria, $criteria);
            }
        }
        return $this;
    }

    public function filterByLanguages()
    {
        $languages = isset($this->data['language']['languages']) ? $this->data['language']['languages'] : [];
        $country   = isset($this->data['language']['country']) ? $this->data['language']['country'] : false;
        $ids       = wp_list_pluck($this->matches, 'ID');

        if (!$ids || empty($this->data['language'])) {
            return $this;
        }

        $criteria = array_map(function($v) {
            return 'languages=supported_languages_' . $v;
        }, $languages);

        global $wpdb;
        $ids      = implode(',', $ids);
        $term_ids = implode(',', $languages);

        if ($country) {
            $results = $wpdb->get_col(
                "SELECT p.ID
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->term_relationships} tr
                    ON tr.object_id = p.ID
                INNER JOIN {$wpdb->term_taxonomy} tt
                    ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.term_id = $country
                WHERE p.ID IN ($ids)
                GROUP BY p.ID"
            );

            foreach ($this->matches as $id => $match) {
                if (in_array($id, $results)) {
                    unset($this->matches[$id]);
                    continue;
                }
                $match->criteria = array_merge($match->criteria, ['languages=forbidden_countries_' . $country]);
            }
            $criteria[] = 'languages=forbidden_countries_' . $country;
        }
        $this->addCriteria($criteria);

        if ($term_ids) {
            $results = $wpdb->get_results(
                "SELECT p.ID, tt.term_id AS lang
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->term_relationships} tr
                    ON tr.object_id = p.ID
                INNER JOIN {$wpdb->term_taxonomy} tt
                    ON tt.term_taxonomy_id = tr.term_taxonomy_id
                WHERE p.ID IN ($ids) AND tt.term_id IN ($term_ids)"
            );
            $results = $results ?: [];

            // Group By ID
            $tmp = [];
            foreach ($results as $row) {
                $tmp[$row->ID][] = $row->lang;
            }
            $results = $tmp;
            unset($tmp, $row);

            foreach ($this->matches as $id => $match) {
                if (isset($results[$id])) {
                    $criteria = array_map(function ($v) {
                        return 'languages=supported_languages_' . $v;
                    }, $results[$id]);
                    $match->criteria = array_merge($match->criteria, $criteria);
                }
            }
        }
        return $this;
    }

    public function filterByCriteria()
    {
        // comfort - reviews_stats_payments_speed
        // reputation - reviews_stats_reliability
        // line - reviews_stats_coefficients
        // support - reviews_stats_support

        $stats['comfort'] = isset($this->data['criteria']['comfort'])
            ? (int)$this->data['criteria']['comfort'] : false;
        $stats['reputation'] = isset($this->data['criteria']['reputation'])
            ? (int)$this->data['criteria']['reputation'] : false;
        $stats['line']    = isset($this->data['criteria']['line'])
            ? (int)$this->data['criteria']['line'] : false;
        $stats['support'] = isset($this->data['criteria']['support'])
            ? (int)$this->data['criteria']['support'] : false;

        $criteria = $criteriaToAdd = [];

        $ids = wp_list_pluck($this->matches, 'ID');

        if (!$ids || empty($this->data['criteria'])) {
            return $this;
        }

        global $wpdb;
        $joins  = [];
        $where  = [];
        $assoc  = [
            'comfort'    => 'reviews_stats_payments_speed',
            'reputation' => 'reviews_stats_reliability',
            'line'       => 'reviews_stats_coefficients',
            'support'    => 'reviews_stats_support',
        ];

        foreach ($stats as $key => $prop) {
            $criteria[] = 'criteria=' . $key;
            if ($prop !== 5) {
                continue;
            }
            $joins[]  =
                "LEFT JOIN {$wpdb->postmeta} {$key}
                ON {$key}.post_id = p.ID AND {$key}.meta_key = '{$assoc[$key]}'";
            $where[] = "CAST({$key}.meta_value AS SIGNED) IN (4,5)";
            $criteriaToAdd[] = 'criteria=' . $key;

        }
        $this->addCriteria($criteria);

        if (!$where) {
            return $this;
        }

        $joins  = implode(PHP_EOL, $joins);
        $where  = implode(' AND ', $where);
        $ids    = implode(',', $ids);

        $results = $wpdb->get_col(
            "SELECT p.ID
            FROM {$wpdb->posts} p
            $joins
            WHERE p.ID IN ($ids) AND $where
            GROUP BY p.ID"
        );
        $results = !is_array($results) ? [] : $results;

        foreach ($this->matches as $id => $match) {
            if (in_array($id, $results)) {
                $match->criteria = array_merge($match->criteria, $criteriaToAdd);
            }
        }
        return $this;
    }

    public function getCriteriaData($criteria)
    {
        $predefined = [
            // PRODUCTS
            'sport'            => [
                'icon' => 'icon-sport',
                'text' => __('Ставки на спорт', 'bmr')
            ],
            'casino'            => [
                'icon' => 'icon-casino',
                'text' => __('Казино', 'bmr')
            ],
            'livecasino'            => [
                'icon' => 'icon-live-casino',
                'text' => __('Живое казино', 'bmr')
            ],
            'poker'            => [
                'icon' => 'icon-poker',
                'text' => __('Покер', 'bmr')
            ],
            'games'            => [
                'icon' => 'icon-games',
                'text' => __('Игры', 'bmr')
            ],
            'financialbetting'            => [
                'icon' => 'icon-betting',
                'text' => __('Финансовый беттинг', 'bmr')
            ],
            'virtualsport'            => [
                'icon' => 'icon-virtual-sport',
                'text' => __('Виртуальный спорт', 'bmr')
            ],
            'bingo'            => [
                'icon' => 'icon-bingo',
                'text' => __('Бинго', 'bmr')
            ],
            'cybersport'            => [
                'icon' => 'icon-cybersport',
                'text' => __('Киберспорт', 'bmr')
            ],
            'totalizator'            => [
                'icon' => 'icon-totalizator',
                'text' => __('Тотализатор', 'bmr')
            ],

            // DEVICES
            'desktop' => [
                'icon' => 'icon-Flaticon_25240',
                'text' => __('Персональный компьютер', 'bmr'),
            ],
            'tablet' => [
                'icon' => 'icon-tablet-01',
                'text' => __('Планшет', 'bmr'),
            ],
            'mobile' => [
                'icon' => 'icon-phone',
                'text' => __('Телефон', 'bmr'),
            ],

            // PLAYER
            'beginner' => [
                'icon' => 'icon-player-beginner',
                'text' => __('Новичок', 'bmr')
            ],
            'amateur' => [
                'icon' => 'icon-player-amateur',
                'text' => __('Любитель', 'bmr')
            ],
            'expert' => [
                'icon' => 'icon-player-expert',
                'text' => __('Профессионал', 'bmr')
            ],

            // FINANCES
            'highs-yes' => [
                'icon' => 'icon-sad',
                'text' => __('Урезали максимумы', 'bmr')
            ],
            'highs-no' => [
                'icon' => 'icon-smiley',
                'text' => __('Не урезали максимумы', 'bmr')
            ],

            // CRITERIA
            'criteria=comfort' => [
                'icon' => 'icon-icon6',
                'text' => __('Удобство платежей', 'bmr')
            ],
            'criteria=reputation' => [
                'icon' => 'icon-star-01',
                'text' => __('Репутация', 'bmr')
            ],
            'criteria=line' => [
                'icon' => 'icon-list',
                'text' => __('Линия', 'bmr')
            ],
            'criteria=support' => [
                'icon' => 'icon-icon52',
                'text' => __('Служба поддержки', 'bmr')
            ]
        ];

        $data = [];

        foreach ($criteria as $cri) {
            $type = explode('=', $cri);

            if (strpos($cri, 'reviews_products') !== false) {
            // PRODUCTS
                $product = str_replace('reviews_products_and_features_', '', $cri);
                $product = str_replace('_', '', $product);
                isset($predefined[$product]) && ($data['products=' . $product] = $predefined[$product]);
            } elseif (count($type) === 2) {
                list($type, $value) = $type;

                if ($type === 'player' || $type === 'devices') {
                // PLAYER && DEVICES
                    isset($predefined[$value]) && ($data[$cri] = $predefined[$value]);
                } elseif ($type === 'finances') {
                // FINANCES
                    if (strpos($value, 'highs') !== false) {
                        $data[$cri] = $predefined[$value];
                    }  elseif (strpos($value, 'supported_currencies') !== false) {
                        $termId = (int)str_replace('supported_currencies_', '', $value);
                        $term = get_term($termId, 'supported_currencies');
                        $name = get_field('currency_name', $term);
                        $name = $name ? $name : $term->name;

                        $data[$cri] = [
                            'icon' => $term->name,
                            'text' => $name,
                            'type' => 'text'
                        ];
                    } else {
                        $avg = (int)explode('-', $value)[1];
                        $data[$cri] = [
                            'icon' => 'icon-icon4',
                            'text' => $avg,
                            'type' => 'icon'
                        ];
                    }
                } elseif ($type === 'payment') {
                // PAYMENT
                    $termId = (int)str_replace('supported_payments_', '', $value);
                    $term = get_term($termId, 'supported_payments');
                    $img = get_field('category_icon', $term);

                    $data[$cri] = [
                        'icon' => $img ? $img : 'No Icon',
                        'text' => $term->name,
                        'type' => $img ? 'img' : 'text'
                    ];
                } elseif ($type === 'languages') {
                // LANGUAGES
                    if (strpos($value, 'supported_languages') !== false) {
                        $tax = 'supported_languages';
                    } elseif (strpos($value, 'forbidden_countries') !== false) {
                        $tax = 'forbidden_countries';
                    }
                    $termId = (int)str_replace($tax . '_', '', $value);
                    $term = get_term($termId, $tax);
                    $img = get_field('category_icon', $term);

                    $data[$cri] = [
                        'icon' => $img ? $img : 'No Icon',
                        'text' => $term->name,
                        'type' => $img ? 'img' : 'text'
                    ];
                } elseif ($type === 'criteria') {
                    isset($predefined[$cri]) && ($data[$cri] = $predefined[$cri]);
                }
            }
        }
        return $data;
    }

    private function filterDevices($devices)
    {
        $data = [];
        foreach($devices as $device) {
            if(preg_match('#(windowsphone|android)#', $device)) {
                $data[] = 'devices=tablet';
                $data[] = 'devices=mobile';
            } elseif ($device === 'iosphone') {
                $data[] = 'devices=mobile';
            } elseif ($device === 'iostablet') {
                $data[] = 'devices=tablet';
            } else {
                $data[] = 'devices=desktop';
            }
        }
        return array_unique($data);
    }

    public function getPartials()
    {
        return [
            'products'  => [
                'arrow_title' => __('Продукт', 'bmr'),
                'heading'     => __('Какие продукты вас интересуют?', 'bmr')
            ],
            'devices'  => [
                'arrow_title' => __('Устройства', 'bmr'),
                'heading'     => __('Какими устройствами вы пользуетесь для ставок?', 'bmr')
            ],
            'player'   => [
                'arrow_title' => __('Игрок', 'bmr'),
                'heading'     => __('Опишите себя как игрока', 'bmr')
            ],
            'time'     => [
                'arrow_title' => __('Время', 'bmr'),
                'heading'     => __('Когда вы обычно делаете ставки?', 'bmr')
            ],
            'finances' => [
                'arrow_title' => __('Финансы', 'bmr'),
                'heading'     => __('Финансы', 'bmr')
            ],
            'payment'  => [
                'arrow_title' => __('Счет', 'bmr'),
                'heading'     => __('Способы пополнения счета', 'bmr')
            ],
            'language' => [
                'arrow_title' => __('Языки', 'bmr'),
                'heading'     => __('Языки', 'bmr')
            ],
            'criteria' => [
                'arrow_title' => __('Критерии', 'bmr'),
                'heading'     => __('Укажите важность этих критериев при выборе конторы', 'bmr')
            ],
            'results'  => [
                'arrow_title' => __('Результаты', 'bmr'),
                'heading'     => __('Подходящие Вам букмекерские конторы:', 'bmr')
            ],
        ];
//        return $this->partials;
    }

    public function getPartialAction()
    {
        // set is_admin() to false
        $GLOBALS['current_screen'] = \WP_Screen::get('front');

        $partialId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
        $locale = sanitize_text_field($_GET['locale']);
        $keys      = array_keys($this->partials);
        $index     = isset($keys[$partialId - 1]) ? $keys[$partialId - 1] : 'product';
        $partial   = BMR_ASSISTANT_PARTIALS . DIRECTORY_SEPARATOR . $index . '.php';

        $response = [
            'success' => false,
            'content' => ''
        ];

        add_filter('locale', function($l) use ($locale) {
            return $locale;
        }, 10, 1);
        load_theme_textdomain('bmr', TEMPLATEPATH .'/languages');

        if (is_file($partial)) {
            ob_start();
            include_once($partial);
            $response['content'] = ob_get_clean();
            $response['success'] = true;

        } else {
            $response['errors'] = [__('Отсутствует файл!', 'bmr')];
        }
        die(json_encode($response));
    }

    public function createPageIfNotExists()
    {
        $page = get_page_by_path('assistant');
        if ($page && $page->post_status !== 'trash') {
            return;
        }

        $pageId = wp_insert_post([
            'post_title'     => __('Ассистент', 'bmr'),
            'post_type'      => 'page',
            'post_name'      => 'assistant',
            'comment_status' => 'closed',
            'post_status'    => 'publish',
        ]);
        $pageId && update_post_meta($pageId, '_bmr_quiz_page_template',  BMT_ASSISTANT_SLUG . '/page-assistant.php');
    }


    public function loadStyles()
    {
        wp_enqueue_style(
            'nativeSelect',
            BASE_THEME_ASSETS . 'components/cool-select/css/cool-select.css',
            [],
            BMR_ASSISTANT_VERSION
        );

        wp_enqueue_style(
            'assistant',
            plugins_url('/assets/css/assistant.css', __FILE__),
            [],
            BMR_ASSISTANT_VERSION
        );
    }

    public function loadScripts()
    {
        wp_enqueue_script(
            'nouislider',
            plugins_url('/assets/js/nouislider.min.js', __FILE__),
            [],
            BMR_ASSISTANT_VERSION,
            true
        );
        wp_enqueue_script(
            'nativeSelect',
            BASE_THEME_ASSETS . 'components/cool-select/cool-select.min.js',
            [],
            BMR_ASSISTANT_VERSION,
            true
        );
        wp_enqueue_script(
            'hammer',
            BASE_THEME_JS_URI . 'hammer.min.js',
            [],
            BMR_ASSISTANT_VERSION,
            true
        );
        wp_enqueue_script(
            'assistant-js',
            plugins_url('/assets/js/assistant.js', __FILE__),
            [],
            BMR_ASSISTANT_VERSION,
            true
        );
        wp_enqueue_script(
            'assistant-footer',
            plugins_url('/assets/js/footer.js', __FILE__),
            [],
            BMR_ASSISTANT_VERSION,
            true
        );
        wp_enqueue_script(
            'bmr-assistant',
            plugins_url('/assets/js/bmr-assistant.js', __FILE__),
            [],
            BMR_ASSISTANT_VERSION,
            true
        );
        wp_localize_script('bmr-assistant', 'quiz', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'i18n'    => [
                'locale'        => get_locale(),
                'tooltip'       => __('Чтобы перейти к следующему вопросу, ответьте на текущий', 'bmr'),
                'highs_cut_yes' => __('Урезали максимумы', 'bmr'),
                'highs_cut_no'  => __('Не урезали максимумы', 'bmr'),
                'matches'       => [
                    'fully'     => [
                        __('Подходящая контора', 'bmr'),
                        __('Подходящие конторы', 'bmr'),
                        __('Подходящих контор', 'bmr')
                    ],
                    'partially' => [
                        __('Частично подходящая', 'bmr'),
                        __('Частично подходящие', 'bmr'),
                        __('Частично подходящих', 'bmr')
                    ]
                ],
            ]
        ]);
    }

    public function isCurrent() {
        return is_page('assistant');
    }

    private function addCriteria($criteria)
    {
        foreach($criteria as $cri) {
            $this->criteria[] = $cri;
        }
        $this->criteria = array_unique($this->criteria);
    }
}