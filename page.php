<?php
/**
 * Single page template file
 *
 * @package  WordPress
 * @subpackage  SageTimber
 * @since  SageTimber 0.1
 */

$context = Timber::get_context();
$context['post'] = New Timber\Post();
add_filter('page_context', 'playful_context_acf_fields');
$context = apply_filters('page_context', $context, $context['post']);

Timber::render('pages/page.twig', $context);