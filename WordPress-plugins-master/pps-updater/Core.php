<?php

namespace PpsUpdater;

class Core
{
    const SLUG = 'pps-updater';

    /**
     * @var Core Singleton instance
     */
    private static $_instance;

    /**
     * @var array $_bookmakers    Bookmakers objects
     * @var array $_themplateVars Variables wich will be send into themplate file
     * @var array $_bookmakerData Current bookmaker data (for steps 2+)
     */
    private $_bookmakers       = [],
            $_themplateVars    = [
                'errorMessages' => [],
            ],
            $_bookmakerData    = [];

    /**
     * Singleton
     *
     * @return Core
     */
    public static function init()
    {
        !self::$_instance && self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * Registers menu, styles and scripts, starts session
     */
    private function __construct()
    {
        // register menu
        add_action('admin_menu', function()
        {
            add_menu_page(
                __('Обновить список ППС', 'pps'), __('Обновить ППС', 'pps'), 'manage_options',
                self::SLUG, [$this, 'showForm'], 'dashicons-admin-site', 81
            );
        }, 10, 0);

        // register styles and scripts
        add_action('admin_enqueue_scripts', function()
        {
            wp_enqueue_style('pps-updater', PPS_UPDATER_ASSETS_URI . '/css/styles.css', [], PPS_UPDATER_VERSION);
        }, 10, 0);

        // start session
        isset($_GET['page']) && $_GET['page'] === self::SLUG && add_action('init', 'session_start', -1, 0);
    }

    private function __clone() {}
    private function __wakeup() {}


    public function showForm()
    {
        $this->_initBookmakers();

        if (isset($_POST['step'])) {
            // set bookmaker data
            //isset($_SESSION['ppsUpdater']['data']) && ($this->_bookmakerData = $_SESSION['ppsUpdater']['data']);

            if ($_POST['step'] == 1 && isset($_POST['bookmaker'])) {
                if (isset($this->_bookmakers[$_POST['bookmaker']])) {
                    /** @var Bookmaker $bookmaker */
                    $bookmaker = $_SESSION['ppsUpdater']['bookmaker'] = $this->_bookmakers[$_POST['bookmaker']];
                    if ($bookmaker->getData($error)) {
                        $this->_bookmakerData = $_SESSION['ppsUpdater']['data'] = $bookmaker->getItems();
                        $this->_addErrorMessage('Данные получены (' . count($bookmaker->getItems()) . ' ППС)!', 'success');
                        $this->_showStep2();

                        return;
                    } else {
                        $this->_addErrorMessage($error, 'error');
                    }
                } else {
                    $this->_addErrorMessage('Ошибка! Некорректный букмекер.', 'error');
                }
            } else if ($_POST['step'] == 2 && isset($_POST['type'], $_POST['services'], $_POST['country'], $_SESSION['ppsUpdater']['bookmaker'])) {
                $updater = new UpdateData($_SESSION['ppsUpdater']['bookmaker'], $_POST['type'], $_POST['services'], $_POST['country']);
                $isUpdated = $updater->update();
                $results = $updater->getResults();

                if ($isUpdated) {
                    $messages = ['Данные обновлены!'];
                    $results['addedCities'] && $messages[] = 'Добавлено новых городов: ' . $results['addedCities'];
                    $results['addedItems'] && $messages[] = 'Добавлено новых ППС: ' . $results['addedItems'];
                    $results['updatedItems'] && $messages[] = 'Обновлено существующих ППС: ' . $results['updatedItems'];
                    $results['deletedItems'] && $messages[] = 'Удалено ППС: ' . $results['deletedItems'];
                } else {
                    $messages = ['Данные актуальны, обновлять нечего.'];
                }
                $this->_addErrorMessage($messages, 'success');

                if ($results['conflicts']) {
                    $this->_addErrorMessage('Конфликтов: ' . $results['conflicts'], 'notice');
                    $this->_showStep3();
                    return;
                }
            }
        }

        $this->_showStep1();
    }


    /**
     * Init bookmakers classes
     *
     * @return void
     */
    private function _initBookmakers()
    {
        foreach (scandir(PPS_UPDATER_BOOKMAKERS) ?: [] as $f) {
            if ($f !== '..' && $f !== '.') {
                $className = __NAMESPACE__ . '\\Bookmakers\\' . strstr($f, '.', true);
                $bookmaker = new $className;
                $bookmaker instanceof \PpsUpdater\Bookmaker && ($this->_bookmakers[$bookmaker->getName()] = $bookmaker);
            }
        }

        ksort($this->_bookmakers);
    }

    /**
     * Shows form for bookmaker selection
     *
     * @return void
     */
    private function _showStep1()
    {
        $bookmakers = array_keys($this->_bookmakers);
        ksort($bookmakers);

        $this->_setThemplateVars([
            'subtitle'   => 'Шаг 1 - Выберите букмекера',
            'bookmakers' => $bookmakers,
        ])->_showThemplate('step1');
    }

    /**
     * Shows types and services selects, getted data
     *
     * @return void
     */
    private function _showStep2()
    {
        $this->_setThemplateVars([
            'subtitle' => 'Шаг 2 - Выберите тип заведения и услуги',
            'types'    => Helper::getPpsTypes(),
            'services' => Helper::getPpsServices(),
            'data'     => $this->_bookmakerData,
            'country'  => $this->_getCountries(),
        ])->_showThemplate('step2');
    }


    private function _showStep3()
    {
        $this->_setThemplateVars([
            'subtitle' => 'Шаг 3 - Конфликты'
        ])->_showThemplate('step3');
    }

    /**
     * Sets template variables
     *
     * @param array $vars
     *
     * @return $this
     */
    private function _setThemplateVars(array $vars)
    {
        $this->_themplateVars = $vars + $this->_themplateVars;

        return $this;
    }

    /**
     * Shows base and passed themplates using themplate variables
     *
     * @param string $template Template file without extension
     *
     * @return void
     */
    private function _showThemplate($template)
    {
        global $title;
        extract($this->_themplateVars);

        ob_start();
        require_once PPS_UPDATER_TEMPLATES . '/' . $template . '.php';
        $content = ob_get_clean();

        require_once PPS_UPDATER_TEMPLATES . '/base.php';
    }

    /**
     * Adds error message into themplate variables
     *
     * @param string $message Error message
     * @param string $type    Error type. It will set as class of message element
     *
     * @return $this
     */
    private function _addErrorMessage($message, $type = '')
    {
        $this->_themplateVars['errorMessages'][] = [
            'message' => implode('<br>', (array) $message),
            'type'    => $type
        ];

        return $this;
    }


    private function _getCountries()
    {
        global $wpdb;

        $data = $wpdb->get_results('
            SELECT t.term_id, t.name
            FROM ' . $wpdb->term_taxonomy . ' AS tt
            INNER JOIN ' . $wpdb->terms . ' AS t USING(term_id)
            WHERE tt.taxonomy = "locations" AND tt.parent = 0
            ORDER BY name
        ', ARRAY_A);

        return array_column($data, 'name', 'term_id');
    }
}