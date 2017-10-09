<div class="assistant-devices is-hidden" id="devices">
    <div class="item desktop">
        <input type="checkbox" name="devices[]" id="web" class="css-checkbox top-level" value="site"/>
        <label for="web" class="css-label-with-img" data-icon="icon-Flaticon_25240">
            <i class="ico ico-desktop needsclick"></i>
            <span class="label-text needsclick"><?php _e('Персональный компьютер', 'bmr') ?></span>
        </label>
    </div>
    <div class="item tablet">
        <div class="overlay-icons">
            <img class="ios-item" src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/devices/ios.svg">
            <img class="android-item" src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/devices/android.svg">
            <img class="wphone-item" src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/devices/wphone.svg">
        </div>
        <input type="checkbox" id="tablet" class="css-checkbox top-level"/>
        <label for="tablet" class="css-label-with-img tablet-label" data-icon="icon-tablet-01">
            <i class="ico ico-tablet needsclick"></i>
            <span class="label-text needsclick"><?php _e('Планшет', 'bmr') ?></span>
        </label>

        <div class="hidden-checkbox">
            <div>
                <input type="checkbox" name="devices[]" id="checkboxG7" class=" css-checkbox" data-type="ios" value="iostablet"/>
                <label for="checkboxG7" class="css-label radGroup1" data-type="ios">iOS</label>
            </div>
            <div>
                <input type="checkbox" name="devices[]" id="checkboxG8" class=" css-checkbox" data-type="android" value="android_tablet"/>
                <label for="checkboxG8" class="css-label radGroup1" data-type="android">Andriod</label>
            </div>
            <div>
                <input type="checkbox" name="devices[]" id="checkboxG9" class=" css-checkbox" data-type="wphone"  value="windowsphone_tablet"/>
                <label for="checkboxG9" class="css-label radGroup1" data-type="wphone">Windows Phone</label>
            </div>
        </div>
    </div>
    <div class="item mobile">
        <div class="overlay-icons">
            <img class="ios-item" src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/devices/ios.svg">
            <img class="android-item" src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/devices/android.svg">
            <img class="wphone-item" src="<?= BMR_ASSISTANT_ASSETS_URI ?>/img/devices/wphone.svg">
        </div>
        <input type="checkbox" id="phone" class="css-checkbox top-level"/>
        <label for="phone" class="css-label-with-img mobile-label" data-icon="icon-phone">
            <i class="ico ico-smartphone needsclick"></i>
             <span class="label-text needsclick"><?php _e('Телефон', 'bmr') ?></span>
        </label>
        <div class="hidden-checkbox">
            <div>
                <input type="checkbox" name="devices[]" id="checkboxG4" class="css-checkbox" data-type="ios" value="iosphone"/>
                <label for="checkboxG4" class="css-label ios radGroup1" data-type="ios">iOS</label>
            </div>
            <div>
                <input type="checkbox" name="devices[]" id="checkboxG5" class="css-checkbox" data-type="android" value="android_phone"/>
                <label for="checkboxG5" class="css-label android radGroup1" data-type="android">Andriod</label>
            </div>
            <div>
                <input type="checkbox" name="devices[]" id="checkboxG6" class="css-checkbox" data-type="wphone" value="windowsphone_phone"/>
                <label for="checkboxG6" class="css-label wphone radGroup1" data-type="wphone">Windows Phone</label>
            </div>
        </div>
    </div>
</div>