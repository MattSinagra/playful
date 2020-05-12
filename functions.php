<?php

//Setup Minimum WP version requirement
// if ( version_compare( $GLOBALS['wp_version'], '5.0', '<' ) ) {
// 	require get_template_directory() . '/inc/back-compat.php';
// 	return;
// }


// Composer Includes
require __DIR__ . '/vendor/autoload.php';

// Enqueue webpack assets
$enqueue = new \WPackio\Enqueue( 'playful', 'dist', '1.0.0', 'theme', __FILE__ );


function  superScripts() {
  global $enqueue;
  $enqueue->enqueue( 'build', 'main', [] );
}
add_action( 'wp_enqueue_scripts', 'superScripts' );

//Enable Timber
$timber = new \Timber\Timber();

// Theme Inclusions 
$theme_includes = [
    'inc/timber.php',             // Timber setup
    'inc/assets.php',             // Timber setup
    'inc/setup.php',             // Timber setup
    'inc/enqueue.php',             // Timber setup
    'inc/acf.php',             // Timber setup

];

foreach ($theme_includes as $file) {
  if (!$filepath = locate_template($file)) {
    trigger_error(sprintf(__('Error locating %s for inclusion', 'snags'), $file), E_USER_ERROR);
  }
  require_once $filepath;
}
unset($file, $filepath);



