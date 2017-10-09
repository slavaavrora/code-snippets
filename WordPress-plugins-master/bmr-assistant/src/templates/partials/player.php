<div class="assistant-player is-hidden" id="player" data-position="beginner">
    <div class="scroll-container">
        <div class="point" data-type="expert"><?php _e('Профессионал', 'bmr') ?></div>
        <div class="point" data-type="amateur"><?php _e('Любитель', 'bmr') ?></div>
        <div class="point" data-type="beginner"><?php _e('Новичок', 'bmr') ?></div>
        <input
            type="hidden"
            name="player[type]"
            id="player-type"
            data-default-label="<?php _e('Новичок', 'bmr') ?>"
            data-label="<?php _e('Новичок', 'bmr') ?>"
            data-default-value="beginner"
            value="beginner"
        >
    </div>
    <div class="player-container">
        <img src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/player/beginner.png" />
        <img src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/player/amateur.png" />
        <img src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/player/expert.png" />
    </div>
    <div class="player-extra">
        <ul>
            <li>
                <input type="radio" name="player[extra]" id="vilochnik" class="css-radio" value="vilochnik"/>
                <label for="vilochnik" class="css-label-radio"><?php _e('Я вилочник', 'bmr') ?></label>
            </li>
            <li>
                <input type="radio" name="player[extra]" id="koridorist" class="css-radio" value="koridorist"/>
                <label for="koridorist" class="css-label-radio"><?php _e('Я коридорист', 'bmr') ?></label>
            </li>
            <li>
                <input type="radio" name="player[extra]" id="valuyschik" class="css-radio" value="valuyschik"/>
                <label for="valuyschik" class="css-label-radio"><?php _e('Я валуйщик', 'bmr') ?></label>
            </li>
            <li>
                <input type="radio" name="player[extra]" id="knopochnik" class="css-radio" value="knopochnik"/>
                <label for="knopochnik" class="css-label-radio"><?php _e('Я кнопочник', 'bmr') ?></label>
            </li>
            <li>
                <input type="radio" name="player[extra]" id="bonushunter" class="css-radio" value="bonushunter"/>
                <label for="bonushunter" class="css-label-radio"><?php _e('Я бонусхантер', 'bmr') ?></label>
            </li>
            <li class="line-height">
                <input type="radio" name="player[extra]" id="strategies" class="css-radio" value="strategies"/>
                <label for="strategies" class="css-label-radio">
                    <?php _e('Я использую стратегии, которые помогают мне выигрывать', 'bmr') ?>
                </label>
            </li>
            <li>
                <input type="radio" name="player[extra]" id="other" class="css-radio" value="other"/>
                <label for="other" class="css-label-radio"><?php _e('Другое', 'bmr') ?></label>
            </li>
            <li>
                <input
                    class="input-control"
                    type="text"
                    placeholder="<?php _e('Введите тип игрока', 'bmr') ?>"
                    name="player[extra]"
                    id="player-type-other"
                    disabled
                />
            </li>
        </ul>
    </div>
</div>