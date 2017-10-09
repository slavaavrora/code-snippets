<div class="assistant-time-content is-hidden" id="time">
    <div class="container">
        <span class="checkbox-group">
            <input type="checkbox" name="time[]" id="checkboxG1" class="css-checkbox" data-type="first" value="0"/>
            <label for="checkboxG1" class="css-label radGroup1 " data-type="first"><?php _e('Задолго до начала матча', 'bmr') ?></label>
        </span>
        <span class="checkbox-group">
            <input type="checkbox" name="time[]" id="checkboxG2" class="css-checkbox" data-type="second" value="1"/>
            <label for="checkboxG2" class="css-label radGroup1 " data-type="second"><?php _e('Непосредственно перед матчем', 'bmr') ?></label>
        </span>
        <span class="checkbox-group">
            <input type="checkbox" name="time[]" id="checkboxG3" class="css-checkbox" data-type="third" value="2"/>
            <label for="checkboxG3" class="css-label radGroup1 " data-type="third"><?php _e('Во время матча LIVE', 'bmr') ?> </label>
        </span>
    </div>
    <div class="item-container">
        <img src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/time/time-pict.svg">
        <img class="guy first " src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/time/guy1.svg">
        <img class="guy second " src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/time/guy2.svg">
        <img class="guy third " src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/time/guy3.svg">
    </div>
</div>