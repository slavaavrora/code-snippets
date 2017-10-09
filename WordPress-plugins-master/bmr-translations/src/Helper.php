<?php
namespace Bmr\Translations;

class Helper
{
    static public function globalRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            if (strpos($dir, 'bbpress') || strpos($dir, 'buddypress')) {
                continue;
            }
            $files = array_merge($files, self::globalRecursive($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }
}