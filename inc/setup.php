<?php

namespace Roots\Sage\Setup;

use Roots\Sage\Assets;

/**
 * Theme setup
 */
function setup()
{
    /**
     * Enable Soil Plugin Features when plugin is activated
     * @link https://roots.io/plugins/soil/
     */
    add_theme_support('soil-clean-up');
    add_theme_support('soil-nav-walker');
    add_theme_support('soil-nice-search');
    add_theme_support('soil-relative-urls');
    
    // Allow support if you need it
    // add_theme_support('woocommerce');

    /**
     * Make theme available for translation
     *
     * Community Translations Below:
     *
     * @link https://github.com/roots/sage-translations
     */
    load_theme_textdomain('sage', get_template_directory() . '/lang');

    /**
     * Enable plugins to manage the document title
     * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Title_Tag
     */
    add_theme_support('title-tag');

    /**
     * Register wp_nav_menu() menus
     * Setup Context Menus
     * @link http://codex.wordpress.org/Function_Reference/register_nav_menus
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'sage')
    ]);

    register_nav_menus([
        'footer_navigation' => __('Footer Navigation', 'sage')
    ]);

    /**
     * Enable post thumbnails
     * @link http://codex.wordpress.org/Post_Thumbnails
     * @link http://codex.wordpress.org/Function_Reference/set_post_thumbnail_size
     * @link http://codex.wordpress.org/Function_Reference/add_image_size
     */
    add_theme_support('post-thumbnails');

    /**
     * Enable Post Formats
     * @link http://codex.wordpress.org/Post_Formats
     * add_theme_support('post-formats', ['aside', 'gallery', 'link', 'image', 'quote', 'video', 'audio']);
     */

    /**
     * Enable HTML5 markup support
     * @link http://codex.wordpress.org/Function_Reference/add_theme_support#HTML5
     * add_theme_support('html5', ['caption', 'comment-form', 'comment-list', 'gallery', 'search-form']);
     */

    /**
     * Use main stylesheet for visual editor
     * To add custom styles edit:
     * @path /assets/styles/layouts/_tinymce.scss
     */
    // add_editor_style(Assets\asset_path('styles/main.css'));
}

add_action('after_setup_theme', __NAMESPACE__ . '\\setup');

/**
 * Register sidebars
 */
//function widgets_init()
//{
//    register_sidebar([
//        'name' => __('Primary', 'sage'),
//        'id' => 'sidebar-primary',
//        'before_widget' => '<section class="widget %1$s %2$s">',
//        'after_widget' => '</section>',
//        'before_title' => '<h3>',
//        'after_title' => '</h3>'
//    ]);
//
//    register_sidebar([
//        'name' => __('Footer', 'sage'),
//        'id' => 'sidebar-footer',
//        'before_widget' => '<section class="widget %1$s %2$s">',
//        'after_widget' => '</section>',
//        'before_title' => '<h3>',
//        'after_title' => '</h3>'
//    ]);
//}
//
//add_action('widgets_init', __NAMESPACE__ . '\\widgets_init');

/**
 * Determine which pages should **NOT** display the sidebar
 */
function display_sidebar()
{
    static $display;

    isset($display) || $display = !in_array(true, [
        /**
         * The sidebar will NOT be displayed if ANY of the following return true.
         * @link https://codex.wordpress.org/Conditional_Tags
         */
        is_404(),
        is_front_page(),
        is_page_template('template-custom.php'),
        is_page(),
        is_archive(),
        is_home(),
        is_singular(),
        is_single()
    ]);

    return apply_filters('sage/display_sidebar', $display);
}

/**
 * Theme assets
 */
function assets()
{
    // wp_enqueue_style('sage/css', Assets\asset_path('styles/main.css'), false, null);

    if (is_single() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    wp_enqueue_style('google/font/merri', '//fonts.googleapis.com/css?family=Merriweather&display=swap');
    wp_enqueue_style('google/font/Noto', '//fonts.googleapis.com/css?family=Noto+Sans+JP');

    // wp_enqueue_script('sage/modernizr', Assets\asset_path('scripts/modernizr.js'), [], null, true);
    // wp_enqueue_script('sage/js', Assets\asset_path('scripts/main.js'), ['jquery'], null, true);
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\assets', 100);


add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\admin_assets', 100);
function admin_assets() {
    // REGISTER ADMIN.JS
    wp_register_script( 'sage/theme_admin', Assets\asset_path('scripts/admin.js'), array('jquery'), 1.0, true );
    wp_localize_script( 'sage/theme_admin', 'theme_var',
        array(
            'upload' => Assets\asset_path('images/acf-thumbnail/'),
        )
    );
    wp_enqueue_script( 'sage/theme_admin');
    wp_enqueue_style('sage/theme_admin_css', Assets\asset_path('styles/admin.css'), false, null);
}

/**
 * Custom Admin Login Logo
 *
 * @requires Site logo to be set in options page
 */
function my_custom_login_logo()
{
    if ($logo = get_field('site_logo', 'option')) {
        if ($logo = wp_get_attachment_image_src($logo)) {
            echo '<style  type="text/css"> h1 a {  background-image:url(' . $logo[0] . ')  !important; } </style>';
        }
    }
}

add_action('login_head', __NAMESPACE__ . '\\my_custom_login_logo');

/**
 * Filters the oEmbed process to run the responsive_embed() function
 */
add_filter('embed_oembed_html', __NAMESPACE__ . '\\responsive_embed', 10, 3);

/**
 * Adds a responsive embed wrapper around oEmbed content
 * @param  string $html The oEmbed markup
 * @param  string $url The URL being embedded
 * @param  array $attr An array of attributes
 * @return string       Updated embed markup
 */
function responsive_embed($html, $url, $attr)
{
    return $html !== '' ? '<div class="embed-container">' . $html . '</div>' : '';
}


/**
 * Setup Woocommerce product - Global
 */

function timber_set_product( $post ) {
    global $product;
    
    if ( is_woocommerce() ) {
        $product = wc_get_product( $post->ID );
    }
}
