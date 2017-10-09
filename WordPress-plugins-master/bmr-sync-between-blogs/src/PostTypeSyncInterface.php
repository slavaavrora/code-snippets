<?php

namespace Bmr\Sync;

interface PostTypeSyncInterface
{
    public function get($limit = -1, $offset = 0);
    public function sync($postId);
    public function count();
}