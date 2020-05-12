<?php
/**
 * The main template file
 *
 * @package  WordPress
 * @subpackage  SageTimber
 * @since  SageTimber 0.1
 */

$context = Timber::get_context();

$context['posts'] = new Timber\PostQuery();
$templates = array( 'pages/index.twig' );

if ( is_home() ) {
    $context['post'] = new Timber\Post(get_option('page_for_posts'));
    
	array_unshift( $templates, 'pages/home.twig' );
}

Timber::render( $templates, $context );