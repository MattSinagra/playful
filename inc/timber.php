<?php 
use Roots\Sage\Setup;

// Check if Timber is not activated
if ( ! class_exists( 'Timber' ) ) {

    add_action( 'admin_notices', function() {
        echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
    } );
    return;

}

// Add the directory of templates in include path
Timber::$dirname = array('templates');

/**
 * Extend TimberSite with site wide properties
 */
class SageTimberTheme extends TimberSite {

    function __construct() {
        add_filter( 'timber_context', array( $this, 'add_to_context' ) );
        parent::__construct();
    }

    function add_to_context( $context ) {

        /* Menu */
        $context['menu'] = new TimberMenu();

        /* Site info */
        $context['site'] = $this;

        /* Site info */
        $context['display_sidebar'] = Setup\display_sidebar();
        $context['sidebar_primary'] = Timber::get_widgets('sidebar-primary');

        return $context;
    }
}
new SageTimberTheme();

if(!function_exists('playful_timber_context_setup')) {
    function playful_timber_context_setup($context) {

        if($primary_menu = new Timber\Menu('primary_navigation')):
            $context['menus']['primary_menu'] = $primary_menu;
        endif;

        if($footer_menu = new Timber\Menu('footer_navigation')):
            $context['menus']['footer_menu'] = $footer_menu;
        endif;

        return $context;
    }
}

add_filter('timber_context', 'playful_timber_context_setup');


if(!function_exists('playful_get_related_posts')):

    function playful_get_related_posts($context, $postIn = null) {
        if (null == $postIn) {
            global $post;
            $post_id = $post->ID;
        } elseif (is_int($postIn)) {
            $post_id = $postIn;
        } elseif (is_string($postIn)) {
            $post_id = intval($postIn);
        } elseif ( $postIn instanceof \WP_Post || $postIn instanceof \Timber\Post) {
            $post_id = $postIn->ID;
        } else {
            return $context;
        }

        $post_type = get_post_type($post_id);
        $num_posts = 2; // References amount of posts to grab
        $allowed_post_types = array('post');

        if(in_array($post_type, $allowed_post_types)):

            $post_categories = get_the_category($post_id);

            if($post_categories && is_array($post_categories) && !empty($post_categories)) {
                $first_category = $post_categories[0]->term_id;
            }

            $post_tags = get_the_tags($post_id);

            if($post_tags && is_array($post_tags) && !empty($post_tags)) {
                $first_tag = $post_tags[0]->term_id;
            }

            $cat_args = array(
                'posts_per_page' => $num_posts,
                'post_type' => $post_type,
                'category__in' => $first_category,
                'post__not_in' => array($post->ID),
            );

            if($cat_posts = new \Timber\PostQuery($cat_args)) {
                if(count($cat_posts) == $num_posts) {
                    $related_posts = $cat_posts;
                } else {
                    $related_posts = null;
                }
            }

            if($related_posts === null) {
                $post_tags = get_the_tags($post_id);

                if($post_tags && is_array($post_tags) && !empty($post_tags)) {
                    $first_tag = $post_tags[0]->term_id;

                    $tag_args = array(
                        'posts_per_page' => $num_posts,
                        'post_type' => $post_type,
                        'tag__in' => $first_tag,
                        'post__not_in' => array($post->ID),
                    );

                    if($tag_posts = new \Timber\PostQuery($tag_args)) {
                        if(count($tag_posts) == $num_posts) {
                            $related_posts = $tag_posts;
                        }
                    }
                }
            }

           if($related_posts === NULL) {
               $normal_args = array(
                   'posts_per_page' => $num_posts,
                   'post_type' => $post_type,
                   'post__not_in' => array($post->ID),
               );

               if($normal_posts = new \Timber\PostQuery($normal_args)) {
                   if(count($normal_posts) == $num_posts) {
                       $related_posts = $normal_posts;
                   } else {
                       return $context;
                   }
               }
           }

            if(isset($related_posts) && !empty($related_posts) && $related_posts !== NULL) {
                $context['related_posts'] = $related_posts;
            }

        endif;

        return $context;
    }
endif;

add_filter('timber_context', 'playful_get_related_posts');