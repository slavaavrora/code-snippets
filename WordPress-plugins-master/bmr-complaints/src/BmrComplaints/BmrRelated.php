<?php

/**
 * Class BmrRelated
 */
class BmrRelated
{
    /**
     * @var int
     */

    private $maxPosts;

    public function __construct()
    {
        $this->maxPosts = 4;
    }

    public static function getRelated($postId, $num = 4)
    {
        if (!isset($postId)) {
            global $post;
            $postId = $post->ID;
        }
        $terms = wp_get_post_terms($postId, BmrConfig::TAXONOMY_COMPLAINT_TYPE);
        $term = $terms[0]->slug;

        $args = array(
            'post_type'                        => get_post_type() !== BmrConfig::POST_TYPE
                ? BmrConfig::POST_TYPE_KAPPER
                : BmrConfig::POST_TYPE,
            'posts_per_page'                   => $num,
            BmrConfig::TAXONOMY_COMPLAINT_TYPE => $term,
            'exclude'                          => $postId
        );
        $relatedPosts = get_posts($args);
        return $relatedPosts;
    }

    public static function getRecent($num = 4)
    {
        $args = array(
            'post_type'      => get_post_type() !== BmrConfig::POST_TYPE
                ? BmrConfig::POST_TYPE_KAPPER
                : BmrConfig::POST_TYPE,
            'posts_per_page' => $num,
            'orderby'        => 'date',
            'order'          => 'DESC'
         );
        $recentPosts = get_posts($args);
        return $recentPosts;
    }

    public function getRelatedShortcode($atts)
    {

    }
} 