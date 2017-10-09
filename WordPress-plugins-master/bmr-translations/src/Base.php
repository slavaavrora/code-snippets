<?php


namespace Bmr\Translations;


class Base
{
    protected $languages,
              $currLang,
              $stats,
              $translations;

    private $pages,
            $_notes         = [],
            $_newTranslates = [];

    public function __construct()
    {
        global $wpdb;
        $wpdb->translations = "{$wpdb->prefix}translations";

        $this->languages = [
            'en'  => __('Английский', 'bmr'),
            'ukr' => __('Украинский', 'bmr'),
            'am'  => __('Армянский', 'bmr'),
        ];

        isset($_GET['findNewTranslations']) && $this->findNewTranslations();
        isset($_GET['deleteUnusedTranslations']) && $this->deleteUnusedTranslations();

        $this->pages        = [];
        $this->currLang     = $this->getCurrentLang();
        $this->stats        = $this->getTranslatedStats();
        $this->translations = $this->getTranslations();
    }

    public function init()
    {
        add_action('admin_menu', [$this, 'registerMenu'], 10, 0);
        add_filter('acf/load_field', [$this, 'localizeAcfLabels'], 10, 1);

        add_action('admin_enqueue_scripts', [$this, 'loadStyles'], 11, 1);
        add_action('admin_enqueue_scripts', [$this, 'loadScripts'], 11, 1);

        add_action('wp_ajax_generate_translation', [$this, 'generateTranslations']);
        add_action('wp_ajax_save_translation', [$this, 'updateTranslation']);
    }

    public function registerMenu()
    {
        $this->pages['main'] = add_menu_page(
            __('Переводы', 'bmr'),
            __('Переводы', 'bmr'),
            'edit_posts',
            'translations',
            [$this, 'showTranslations'],
            'dashicons-admin-site',
            81
        );

        $this->pages['settings'] = add_submenu_page(
            'translations',
            __('Настройки переводов', 'bmr'),
            __('Настройки', 'bmr'),
            'edit_posts',
            'translations-settings',
            [$this, 'showTranslationsSettings']
        );
    }

    public function showTranslations()
    {
        include_once BMR_TRANSLATIONS_TEMPLATES . '/translations.php';
    }

    public function showTranslationsSettings()
    {
        include_once BMR_TRANSLATIONS_TEMPLATES . '/settings.php';
    }

    public function getCurrentLang()
    {
        return (isset($_REQUEST['lang']) && isset($this->languages[$_REQUEST['lang']]) ? mb_strtolower($_REQUEST['lang']) : 'en');
    }

    public function getTranslatedStats()
    {
        global $wpdb;

        $total      = (int)$wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->translations}");
        $translated = (int)$wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->translations} WHERE translation_{$this->currLang} <> ''");
        $left       = (int)$wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->translations} WHERE translation_{$this->currLang} = ''");

        return [
            'total'      => $total,
            'translated' => $translated,
            'left'       => $left
        ];
    }

    public function getTranslations($isOnlyOriginals = false)
    {
        global $wpdb;

        if ($isOnlyOriginals) {
            $sql = 'SELECT ID, original FROM ' . $wpdb->translations;
        } else {
            $order = !empty($_GET['sort-by'])
                ? "ORDER BY IF(translation_{$this->currLang} = '' OR translation_{$this->currLang} IS NULL, 0, 1)" : '';

            $sql = "SELECT ID, original, translation_{$this->currLang} as translation, note FROM {$wpdb->translations} $order";
        }

        $translations = $wpdb->get_results($sql);

        return !is_array($translations) ? [] : $translations;
    }

    public function getAcfLabels()
    {
        global $wpdb;

        $fields = $wpdb->get_col(
            "SELECT meta_value FROM wp_postmeta WHERE meta_key LIKE 'field_%'"
        );
        $labels = array_map(function($v) {
            preg_match('/label";s:\d+:"([^"]+)"/', $v, $matches);
            return $matches[1];
        }, $fields);

        $labels = array_unique($labels);
        return $labels;
    }

    public function localizeAcfLabels($field)
    {
        $field['label'] = __($field['label'], 'bmr');
        return $field;
    }

    public function parseFileForStrings($filePath)
    {
        global $arr, $notes;

        $foundNote = false;
        $cnt = 0;

        $handle = @fopen($filePath, "r");
        if ($handle) {
            while (($buffer = fgets($handle)) !== false) {

                if (!$foundNote) {
                    $foundNote = preg_match('/translators:(.*)\\*\\//', $buffer, $notesMathes);
                    if ($foundNote) {
                        $foundNote = $notesMathes[1];
                    }
                }

                $matched = preg_match_all('/_[_ex]\\s*\\(\\s*([\'"])(.+?)\\1\\s*,\\s*([\'"])(?:bmr|base)\\3\\s*\\)/i', $buffer, $matches);
                if($matched) {
                    if ($foundNote) {
                        foreach ($matches[2] as $match) {
                            $this->_notes[$match] = $foundNote;
                        }
                        $foundNote = false;
                    }
                    $this->_newTranslates = array_merge($this->_newTranslates,$matches[2]);
                }
            }
            if (!feof($handle)) {
                echo "Error!\n";
            }
            fclose($handle);
        }
    }

    public function getExistingTranslations()
    {
        $fileMask      = '*.php';
        $paths = [
            'bmr_theme'                     => WP_CONTENT_DIR . '/themes/bmr/',
            'basee'                         => WP_CONTENT_DIR . '/themes/base/',
            'bmr_complaints'                => WP_PLUGIN_DIR . '/bmr-complaints/',
            'bmr_auth'                      => WP_PLUGIN_DIR . '/bmr-auth/',
            'bmr_assistant'                 => WP_PLUGIN_DIR . '/bmr-assistant/',
            'post-type-archive-description' => WP_PLUGIN_DIR . '/post-type-archive-description/',
            'bmr-feedbacks'                 => WP_PLUGIN_DIR . '/bmr-feedbacks/',
        ];

        $excludePaths = [
            //WP_CONTENT_DIR . '/themes/bmr/single-bookreviews.php',
            WP_CONTENT_DIR . '/themes/bmr/templates/partials/bookreviews/_datatransfer.php',
        ];
        /*$path = WP_CONTENT_DIR . '/themes/bmr/templates/partials/bookreviews/';
        foreach (scandir($path) as $f) {
            $f !== '.' && $f !== '..' && $excludePaths[] = $path . $f;
        }*/

        foreach ($paths as $path) {
            $files = Helper::globalRecursive($path .  $fileMask);
            $files = array_diff($files, $excludePaths);

            array_map([$this, 'parseFileForStrings'], $files);
        }

        $translates = $this->_newTranslates;
//        $translates = array_merge($translates, $this->getAcfLabels());
        $translates = array_unique($translates);

        return $translates;
    }

    public function findNewTranslations()
    {
        global $wpdb;

        $translates = $this->getExistingTranslations();

        $currentTranslates = $wpdb->get_col('SELECT original FROM ' . $wpdb->translations);
        $translates = array_diff($translates, $currentTranslates);

        foreach ($translates as $t) {
            $wpdb->query('
                INSERT INTO ' . $wpdb->translations . '
                (original, note)
                VALUES("' . esc_sql($t) . '", "' . esc_sql(isset($this->_notes[$t]) ? $this->_notes[$t] : '') . '")
            ');
        }
    }

    public function deleteUnusedTranslations()
    {
        $exist = $this->getExistingTranslations();
        $db = $this->getTranslations(true);

        $dbData = [];
        foreach ($db as $item) {
            $dbData[$item->ID] = $item->original;
        }

        $dbData = array_diff($dbData, $exist);

        if ($dbData) {
            global $wpdb;
            $wpdb->query('
                DELETE FROM ' . $wpdb->translations . '
                WHERE ID IN (' . implode(',', array_keys($dbData)) . ')
            ');
        }
    }

    // ======================================================================================================

    /**
     * Generates .po and .mo translation files
     */
    public function generateTranslations()
    {
        $langCodes = [
            'ukr' => 'uk',
            'en'  => 'en_US',
            'am'  => 'hy'
        ];
        $langFile   = $langCodes[$this->currLang] . '.po';
        $translations = $this->getTranslations();

        $potHeader    = <<<EOT
msgid ""
msgstr ""
"Project-Id-Version: Bmr\\n"
"POT-Creation-Date: 2015-06-02 12:21+0300\\n"
"PO-Revision-Date: 2015-06-02 12:22+0300\\n"
"Last-Translator: \\n"
"Language-Team: \\n"
"Language: Russian\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"X-Generator: Poedit 1.5.4\\n"
"X-Poedit-KeywordsList: _;gettext;gettext_noop;_e;__\\n"
"X-Poedit-Basepath: .\\n"
"X-Poedit-SourceCharset: UTF-8\\n"
"X-Poedit-SearchPath-0: ..\\n"

EOT;
        if (!is_dir(BMR_TRANSLATIONS_THEME_LANG_DIR)) {
            $oldUmask = umask(0);
            mkdir(BMR_TRANSLATIONS_THEME_LANG_DIR, 0775);
            umask($oldUmask);
        }
        $handle = @fopen(BMR_TRANSLATIONS_THEME_LANG_DIR . DIRECTORY_SEPARATOR . $langFile, 'wb');

        if (!$handle) {
            die('Can\'t open the file');
        }

        fwrite($handle, $potHeader);
        foreach($translations as $tObj) {

            $data   = '';

            // With this translations containing ",' etc, not working !!!
            // $msgid  = str_replace(['\\', '"'], ['\\\\', '\\"'], $tObj->original);
            // $msgstr = str_replace(['\\', '"'], ['\\\\', '\\"'], trim($tObj->translation));

            $msgid  = $tObj->original;
            $msgstr = trim($tObj->translation);

            if (empty($msgstr)) {
                continue;
            }

            $data .= '# ID: ' . $tObj->ID . PHP_EOL;
            $data .= 'msgid "' . $msgid . '"' . PHP_EOL;
            $data .= 'msgstr "' . $msgstr . '"' . PHP_EOL . PHP_EOL;

            fwrite($handle, $data);
        }
        fclose($handle);
        $poConvert = new PoConvert();
        $poConvert->phpmo_convert(BMR_TRANSLATIONS_THEME_LANG_DIR . DIRECTORY_SEPARATOR . $langFile);
    }

    public function updateTranslation()
    {
        $response = [
            'success' => false
        ];

        $translation = str_replace('$', '&#36;', wp_unslash(trim($_POST['translation'])));

        /** var wpdb $wpdb */
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->translations,
            ['translation_' . $this->currLang => $translation],
            ['ID' => $_POST['translation_id']],
            ['%s'],
            ['%d']
        );

        if ($result !== false) {
            $response['success'] = true;
        }
        exit(json_encode($response));
    }

    public function loadStyles($hook)
    {
        if (!in_array($hook, $this->pages)) {
            return;
        }

        wp_enqueue_style('bk-icons', get_theme_root_uri() . '/base/assets/fonts/icons/style.css', array(), BMR_TRANSLATIONS_VERSION);
        wp_enqueue_style('ui-kit', get_template_directory_uri() . '/assets/css/ui.css', array(), BMR_TRANSLATIONS_VERSION);

        wp_enqueue_style(
            'bmr-translations',
            plugins_url('/assets/css/bmr-translations.css', __FILE__),
            array(),
            BMR_TRANSLATIONS_VERSION
        );
    }

    public function loadScripts($hook)
    {
        if (!in_array($hook, $this->pages)) {
            return;
        }

        wp_enqueue_script(
            'bmr-translations',
            plugins_url('/assets/js/bmr-translations.js', __FILE__),
            array('jquery'),
            BMR_TRANSLATIONS_VERSION,
            true
        );
        wp_localize_script(
            'bmr-translations',
            'bmrTranslations',
            [
                'ajaxurl'    => admin_url('admin-ajax.php'),
            ]
        );

    }

    public static function createTranslationTable()
    {
        global $wpdb;

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}translations (
                 `ID` int(11) NOT NULL AUTO_INCREMENT,
                `original` text NOT NULL,
                `translation_en` text NOT NULL,
                `translation_ukr` text NOT NULL,
                `translation_am` text NOT NULL,
                `note` varchar(255) DEFAULT '',
                PRIMARY KEY (`ID`),
                FULLTEXT KEY `original` (`original`),
                FULLTEXT KEY `translation_en` (`translation_en`),
                FULLTEXT KEY `translation_ukr` (`translation_ukr`),
                FULLTEXT KEY `translation_am` (`translation_am`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;"
        );
    }

    public function activate()
    {
        self::createTranslationTable();
    }

    public function deactivate() {}
}