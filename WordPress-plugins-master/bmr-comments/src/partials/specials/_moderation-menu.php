<?php $canModerate = \Bmr\Comments\User::userCanEditComments(get_current_user_id()); ?>
<?php if($canModerate): ?>
    <ul id="comment-moderation-menu" class="is-hidden" role="menubar" data-comment-status="">
        <li role="menuitem">
            <a
                class="moderation-menu-item"
                data-action="spam"
                href="#spam"
            >
                <?php _e('Пометить как спам', 'bmr') ?>
            </a>
        </li>
        <li role="menuitem">
            <a
                class="moderation-menu-item"
                data-action="trash"
                href="#trash"
            >
                <?php _e('Удалить', 'bmr') ?>
            </a>
        </li>
        <li role="menuitem">
            <a
                class="moderation-menu-item"
                data-action="blacklist"
                href="#blacklist"
            >
                <?php _e('Черный список', 'bmr') ?>
            </a>
        </li>
        <li role="menuitem">
            <a
                class="moderation-menu-item"
                data-action="edit"
                href="<?= admin_url('comment.php?action=editcomment&c={id}') ?>"
                target="_blank"
            >
                <?php _e('Модерировать', 'bmr') ?>
            </a>
        </li>
        <li role="menuitem">
            <a
                class="moderation-menu-item"
                data-action="pin"
                href="#pin"
            >
                <?php _e('Пометить как особый', 'bmr') ?>
            </a>
        </li>
        <li role="menuitem">
            <a
                class="moderation-menu-item"
                data-action="unpin"
                href="#unpin"
            >
                <?php _e('Убрать отметку "особый"', 'bmr') ?>
            </a>
        </li>
    </ul>
<?php endif; ?>
