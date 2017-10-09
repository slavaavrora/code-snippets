<div class="wrap translations-page">
    <h2><?php echo esc_html(get_admin_page_title()) ?></h2>

    <div class="translation-progress">
        <?php printf(
            __('Переведено %d из %d (%d%%)', 'bmr'),
            $this->stats['translated'],
            $this->stats['total'],
            floor(($this->stats['translated'] / $this->stats['total']) * 100)
        ) ?>
    </div>
    <div>
        <a href="<?= add_query_arg('sort-by', 1) ?>">Показывать непереведенные первыми</a>
    </div>
    <div>
        <select id="lang-select">
            <?php foreach ($this->languages as $val => $text): ?>
                <option value="<?= $val ?>" <?php selected($val, $this->currLang, true) ?>><?= $text ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <form>
        <?php foreach($this->translations as $tr): ?>
            <div class="spinner-container"><i class="icon-spinner icon-spin"></i></div>
            <div class="original"><?php echo esc_html($tr->original) ?></div>
            <textarea class="translation js-translation-change" name="txt_<?php echo $tr->ID ?>" placeholder="<?php _e('Перевод', 'bmr') ?>"><?php echo $tr->translation ?></textarea>
            <?php if (!empty($tr->note)): ?>
                <div class="notes"><?php echo $tr->note ?></div>
            <?php endif ;?>
            <div class="helper"></div>
        <?php endforeach; ?>
    </form>
    <div class="btn-container">
        <button class="button-default button-has-icons js-generate-translation" type="submit">
            <i class="icon-loop icon-spin"></i>
            <?php _e('Сгенерировать переводы', 'bmr') ?>
            <i class="icon-loop icon-spin"></i>
        </button>
    </div>

</div>