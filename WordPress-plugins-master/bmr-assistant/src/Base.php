<?php


namespace Bmr\Assistant;


class Base
{
    public $quizes;
    public $currentQuiz;

    public function init()
    {
        global $wpdb;
        $wpdb->quiz = $wpdb->prefix . 'bmr_quiz';
        $this->addQuiz(new FirstQuiz());

        add_filter('template_include', [$this, 'loadTemplate'], 10, 1);
        add_action('wp', [$this, 'afterLoaded'], 10);
        add_action('init', [$this, 'afterActivate']);
        add_action('wp_enqueue_scripts', [$this, 'loadStyles'], 11);
        add_action('wp_enqueue_scripts', [$this, 'loadScripts'], 11);
        add_action('wp_footer', [$this, 'renderAssistantPopup']);
    }

    public function afterLoaded()
    {
        // Set quiz specific hooks
        foreach($this->quizes as $index => $quiz) {
            if (!$quiz->isCurrent()) {
                continue;
            }
            add_action('wp_enqueue_scripts', [$quiz, 'loadStyles'], 11);
            add_action('wp_enqueue_scripts', [$quiz, 'loadScripts'], 11);
            $this->currentQuiz = $index;
            break;
        }
    }

    public function afterActivate()
    {
        if (get_option('bmr-assistant-activated')) {
            delete_option('bmr-assistant-activated');

            foreach($this->quizes as $quiz) {
                /** @var QuizInterface $quiz */
                $quiz->createPageIfNotExists();
            }
            $this->createTable();
        }
    }

    private function createTable()
    {
        global $wpdb;
        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}bmr_quiz` (
                `quiz_id` smallint(6) unsigned NOT NULL,
                `user_id` int(11) unsigned NOT NULL,
                `result` text NOT NULL,
                `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY (`quiz_id`,`user_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;"
        );
    }

    public function loadTemplate($template)
    {
        $pageTemplate = $this->getTemplate();

        if (strpos($pageTemplate, BMT_ASSISTANT_SLUG) !== false) {
            $GLOBALS['quizMenu'] = $this->quizes[$this->currentQuiz]->getPartials();
            $template = $this->getTemplateHierarchy($pageTemplate);
        }
        return $template;
    }

    protected function getTemplateHierarchy($template)
    {
        if ($themeFile = locate_template([$template])) {
            $file = $themeFile;
        } else {
            $file = BMR_ASSISTANT_TEMPLATES . str_replace(BMT_ASSISTANT_SLUG, '', $template);
        }
        return $file;
    }

    protected function getTemplate()
    {
        global $post;
        return (string)get_post_meta($post->ID, '_bmr_quiz_page_template', true);
    }

    public static function getTemplatePart($slug, $name = '')
    {
        $name = $name !== '' ? '-' . $name : '';
        require(BMR_ASSISTANT_PARTIALS . DIRECTORY_SEPARATOR . $slug . $name . '.php');
    }

    public function addQuiz(QuizInterface $quiz)
    {
        $this->quizes[] = $quiz;
    }

    public static function activate()
    {
        add_option('bmr-assistant-activated', 1);
    }

    public static function deactivate()
    {

    }

    public function loadStyles()
    {
        wp_enqueue_style(
            'bmr-assistant-popup',
            plugins_url('/assets/css/assistant-popup.css', __FILE__),
            [],
            BMR_ASSISTANT_VERSION
        );
    }

    public function loadScripts()
    {
        wp_enqueue_script(
            'bmr-assistant-popup',
            plugins_url('/assets/js/assistant-popup.js', __FILE__),
            [],
            BMR_ASSISTANT_VERSION,
            true
        );
    }

    public function renderAssistantPopup()
    {
        ?>
        <div id="assistant-popup">
            <div class="assistant-popup">
                <div class="assistant-popup-content">
                    <h2 class="assistant-popup-heading">
                        <?php _e('Ищете лучшего букмекера?', 'bmr') ?>
                        <i class="icon-close assistant-popup-close"></i>
                    </h2>
                    <p class="assistant-popup-text">
                        <?php _e('Наш личный ассистент поможет<br>подобрать Вам оптимальный вариант!', 'bmr') ?>
                    </p>
                    <div class="assistant-popup-img">
                        <img
                            src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/common/assistant-robot.png"
                            alt="<?php _e('Личный ассистент', 'bmr') ?>"
                        >
                    </div>
                </div>
                <a
                    href="<?= get_permalink(get_page_by_path('assistant')) ?>"
                    class="button-default assistant-popup-button"
                >
                    <?php _e('Пройти тест', 'bmr') ?>
                </a>
            </div>
        </div>
        <?php
    }
}