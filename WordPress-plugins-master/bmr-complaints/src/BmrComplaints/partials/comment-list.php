<?php
$walker = new BmrCommentWalker();
$output = $walker->walk($comments,0);

if (count($comments) > 0):
?>

    <ol class="comment-list list-unstyled"> <?php
        echo $output;
        ?></ol> <?php
endif;
?>
