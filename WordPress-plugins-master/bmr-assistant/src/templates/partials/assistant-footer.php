<section class="assistant-footer">
    <div class="assistant-panel">
        <div class="panel left">
            <div class="panel-text">
                <span class="chosen-text"><?php _e('Выбранные критерии:', 'bmr') ?></span>
                <a class="edit-btn" href="#" data-swap-text="<?php _e('Скрыть', 'bmr') ?>"><?php _e('Редактировать', 'bmr') ?></a>
            </div>
            <div class="panel-items">
                <div id="last-items" class="items"></div>
                <span id="more-items-counter" class="items-counter" data-num="0"></span>
            </div>
        </div>
        <div class="panel right">
            <div class="matches-number-container">
                <div class="matches-number" id="matched-fully">
                    <div class="n" data-num="0">0<br>1<br>2<br>3<br>4<br>5<br>6<br>7<br>8<br>9</div>
                    <div class="n" data-num="0">0<br>1<br>2<br>3<br>4<br>5<br>6<br>7<br>8<br>9</div>
                    <div class="n" data-num="0">0<br>1<br>2<br>3<br>4<br>5<br>6<br>7<br>8<br>9</div>
                    <span class="matches-text"><?php _e('подходящих контор', 'bmr') ?></span>
                </div>
                <div class="matches-number is-hidden" id="matched-partially">
                    <div class="n" data-num="0">0<br>1<br>2<br>3<br>4<br>5<br>6<br>7<br>8<br>9</div>
                    <div class="n" data-num="0">0<br>1<br>2<br>3<br>4<br>5<br>6<br>7<br>8<br>9</div>
                    <div class="n" data-num="0">0<br>1<br>2<br>3<br>4<br>5<br>6<br>7<br>8<br>9</div>
                    <span class="matches-text">ч<?php _e('частично подходящих', 'bmr') ?></span>
                </div>
            </div>
            <a
                class="button-default button-default-s result-btn"
                href="#results"
                data-swap-text="<?php _e('Пройти заново', 'bmr') ?>"
            >
                <?php _e('К Результатам!', 'bmr') ?>
            </a>
        </div>
    </div>
    <div id="assistant-criteria" class="items"></div>
</section>
