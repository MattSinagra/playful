<?php
if ( ! defined('ABSPATH') ) { die; }

/**
 * This function is kinda cool.
 * Each "page" is a WP Post, that pretty much automatically gets fed to the templates.
 * Because of the way sites are built, each page has ACF Fields attached to it.
 * There's not really a "page" object holder for Timber/Twig so we kinda take those fields and add them straight into
 * the top level of the $context.
 *
 * @Instead-of
 *      $layouts = get_field('page-builder', $post);
 *      foreach( $layouts as $key => $layout ):
 *          ...
 *      endforeach;
 * @example
 *      {% for layout in layouts %}
 *          ...
 *      {% endfor %}
 * @usage
 *      $context = atira_context_acf_fields($context, $context['post']);
 */
if ( ! function_exists('playful_context_acf_fields') ) {
    function playful_context_acf_fields( $context, $postIn = null ) {
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
        $fields = get_fields($post_id);
        if (is_array($fields) && !empty($fields)) {
            foreach ($fields as $idx => &$field) {
                $field = acf_to_timber($field);
                if (!isset($context[$idx])) {
                    $context[$idx] = $field;
                }
            }
        }
        return $context;
    }
}

/**
 * This will grab all fields from ACF's Options page and insert them into the context under ['options']
 * They may be accessed such as {{ options.site_logo }}
 * @param $context
 * @return mixed
 */
function acf_timber_add_options( $context ) {
    $options = get_fields('options');
    if ( ! isset($context['options'])) {
        $context['options'] = array();
    }
    if (is_array($options)) {
        foreach ($options as $key => $option) {
            if (!isset($context['options'][$key])) {
                $context['options'][$key] = $option;
            }
        }
    }
    return $context;
}

/**
 * When fed a field or array of fields, it will iterate through and convert the contents to a format Timber/Twig will
 * understand.
 * For example, images become Timber\Image objects, posts become Timber\Post objects.
 *
 * @param $field
 * @param null $context
 * @return mixed|\Timber\Image|\Timber\Post
 */
function acf_to_timber( $field, &$context = null ) {
    if ( is_array($field) ) {
        foreach ($field as $i => &$child_field) {
            if (is_array($child_field)) {
                $child_field = acf_to_timber($child_field, $context);
            }
        }
        if (isset( $field['acf_fc_layout'] )) {
            $field = acf_handle_layout($field, $context);
        } elseif ( isset( $field['type'] ) ) {
            if ( 'image' == $field['type'] && isset($field['url']) && isset($field['sizes']) ) {
                $field = new \Timber\Image($field);
            }
        }
    } elseif ( $field instanceof WP_Post ) {
        $field = new \Timber\Post( $field );
    }
    return $field;
}


/**
 * A rather silly function that was built for a page builder type thing with 'flexible content'.
 * Basically it takes the acf_fc_layout field (from flexible content type) and from there determines how to handle
 * the layout, prepping any data and stuff like that.
 * @param $field
 * @param null $context
 * @return mixed
 */
function acf_handle_layout($field, &$context = null) {
    switch($field['acf_fc_layout']) {

        case 'text_block':

            break;

        case 'two_column_text_block':

            break;

        case 'image_w_caption':

            /**
             *
             * $field:
             * array (size=5)
             * @key  'acf_fc_layout' => string 'image_w_caption' (length=15)
             * @key  'image' => boolean
             * @key  'caption' => string
             * @key  'image_placement' => string
             * @values left || right
             * @key  'image_type' => string
             * @values square || rectangular
             * @key  'caption_placement' => string
             * @values normal (top) || middle (middle)
             *
             */
                $col_left_classes = []; // Image Column
                $col_right_classes = []; // Caption Column
                $image_classes = []; // Image
                $caption_wrapper_classes = []; // Caption Wrapper

                $image_type = $field['image_type'];
                $image_placement = $field['image_placement'];
                $caption_placement = $field['caption_placement'];

                if( $image_placement == 'left' ):
                    array_push($col_left_classes, "col", "col-12", "col-md-6");
                    array_push($col_right_classes, "col", "col-12", "col-md-6");
                elseif( $image_placement == 'right' ): // right
                    array_push($col_left_classes, "col", "col-12", "col-md-6", "order-md-2");
                    array_push($col_right_classes, "col", "col-12", "col-md-6", "order-md-1");
                else:
                    array_push($col_left_classes, "col", "col-12", "col-md-6");
                    array_push($col_right_classes, "col", "col-12", "col-md-6");
                endif;

                if( $image_type == 'square' ):
                    array_push( $image_classes, "page-builder--image-w-caption_image", "image-square", "background-image", "mb-2", "mb-md-0" );
                elseif( $image_type == 'rectangular' ):
                    array_push( $image_classes, "page-builder--image-w-caption_image", "image-rectangle", "background-image", "mb-2", "mb-md-0" );
                else:
                    array_push( $image_classes, "page-builder--image-w-caption_image", "image-square", "background-image", "mb-2", "mb-md-0" );
                endif;

                if( $caption_placement == 'normal' ): // top
                    array_push($caption_wrapper_classes, "page-builder--image-w-caption_content-wrapper", "content-normal");
                elseif( $caption_placement == 'middle' ): // middle
                    array_push($caption_wrapper_classes, "page-builder--image-w-caption_content-wrapper", "content-middle", "d-md-flex", "align-items-md-center", "justify-content-md-center", "flex-md-column");
                    array_push($col_right_classes, "d-md-flex");
                else:
                    array_push($caption_wrapper_classes, "page-builder--image-w-caption_content-wrapper", "content-normal");
                endif;

                $field['caption_wrapper_classes'] = $caption_wrapper_classes;
                $field['image_classes'] = $image_classes;
                $field['left_col_classes'] = $col_left_classes;
                $field['right_col_classes'] = $col_right_classes;
            break;

        case 'page_slider':

            break;

        case 'banner_image':


            break;
    }
    return $field;
}

/**
 * This will get all fields for a post and insert them into the context.
 * @param $context
 * @return mixed
 */
function acf_timber_add_fields( $context ) {
    $id = get_the_ID();
    if ( ! empty($id) ) {
        $fields = get_fields( get_the_ID() );

        if ( is_array($fields) ) {
            foreach( $fields as $i => &$field ) {
                $field = acf_to_timber($field);
                $context[$i] = $field;
            }
        }

    }
    return $context;
}

add_filter( 'timber_context', 'acf_timber_add_options', 10, 1 );
add_filter( 'timber_context', 'acf_timber_add_fields', 10, 1 );

/**
 * This one speaks for itself... I hope.
 * It activates the acf options page.
 */
if ( function_exists('acf_add_options_page') ) {
    // add parent
    $parent = acf_add_options_page(array(
        'page_title' 	=> 'Theme General Settings',
        'menu_title' 	=> 'Theme Settings',
        'redirect' 		=> false
    ));

    // add sub page
    acf_add_options_sub_page(array(
        'page_title' 	=> 'Business/Company Details',
        'menu_title' 	=> 'Business Details',
        'parent_slug' 	=> $parent['menu_slug'],
    ));

    // add sub page
    acf_add_options_sub_page(array(
        'page_title' 	=> 'Theme Image Settings',
        'menu_title' 	=> 'Theme Images',
        'parent_slug' 	=> $parent['menu_slug'],
    ));
}

/**
 * Oooh you're gonna like this one.
 *
 * All those fancy-pants fields on the options page...
 * This will put them into an "application/ld+json" field for SEO purposes.
 */
function output_schema() {

    $sameAsLinks = array();

    if ($sameAs = get_field('social_media', 'options')) {
        foreach($sameAs as $entry) {
            if($entry['social_media_type'] == 'social'):
                $sameAsLinks[] = '"' . esc_url($entry['social_media_link']) . '"';
            else:
                // Sometimes its better to do nothing
            endif;
        }
    }

    $options = get_fields('options');
    if ( ! is_array( $options ) ) return;
    extract($options);

    $business_name = $options['meta_name'];
    $business_logo = home_url() . $options['site_logo']['url'];
    $business_description = $options['meta_description'];
    $business_phone = $options['meta_phone'];
    $business_mobile = $options['meta_mobile'];
    $business_email = $options['meta_email'];
    $business_locations_count = $options['business_locations_count'];
    $business_single_locations = null;
    $business_multiple_locations = null;

    if(isset($options['single_business_location_details'])):
        $business_single_locations = $options['single_business_location_details'];
    endif; 

    if(isset($options['multiple_business_location_details'])):
        $business_multiple_locations = $options['multiple_business_location_details'];
    endif; 

    $business_rating_info = array(
        'rating_value' => $options['rating_value'],
        'rating_best' => $options['rating_best'],
        'rating_count' => $options['rating_count'],
        'rating_link' => $options['rating_link']
    );
    ?>
    <script type="application/ld+json">
    {
        "@context": "http://schema.org",
        "@type": "ProfessionalService",
        <?php if($business_locations_count == 'one'): ?>
            <?php if( isset($business_single_locations) && !empty($business_single_locations) ): ?>
                <?php if( !empty( $business_single_locations['address_suburb'] ) && !empty( $business_single_locations['address_region'] ) && !empty( $business_single_locations['address_postcode'] ) && !empty( $business_single_locations['address_street'] )  ): ?>
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "<?php echo $business_single_locations['address_suburb']; ?>",
            "addressRegion": "<?php echo $business_single_locations['address_region']; ?>",
            "postalCode": "<?php echo $business_single_locations['address_postcode']; ?>",
            "streetAddress": "<?php echo $business_single_locations['address_street']; ?>"
        },
                <?php endif; ?>
            <?php endif; ?>
        <?php elseif($business_locations_count == 'more'): ?>
            <?php if( isset($business_multiple_locations) && !empty($business_multiple_locations) ): ?>
                <?php $len = count($business_multiple_locations); ?>
                <?php $i = 0; ?>
        "address":
        [
                    <?php foreach($business_multiple_locations as $location): ?>
                        <?php if( !empty( $location['address_suburb'] ) && !empty( $location['address_region'] ) && !empty( $location['address_postcode'] ) && !empty( $location['address_street'] )  ): ?>
                            <?php if($i == $len - 1): ?>
            {
                "@type": "PostalAddress",
                "addressLocality": "<?php echo $location['address_suburb']; ?>",
                "addressRegion": "<?php echo $location['address_region']; ?>",
                "postalCode": "<?php echo $location['address_postcode']; ?>",
                "streetAddress": "<?php echo $location['address_street']; ?>"
            }
                            <?php else: ?>
            {
                "@type": "PostalAddress",
                "addressLocality": "<?php echo $location['address_suburb']; ?>",
                "addressRegion": "<?php echo $location['address_region']; ?>",
                "postalCode": "<?php echo $location['address_postcode']; ?>",
                "streetAddress": "<?php echo $location['address_street']; ?>"
            },
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php $i++; ?>
                    <?php endforeach; ?>
        ],
            <?php endif; ?>
        <?php endif; ?>
        "url": "<?php echo home_url(); ?>",
        "description": "<?php echo $business_description; ?>",
        "name": "<?php echo $business_name; ?>",
        "telephone": "<?php echo $business_phone; ?>",
        <?php if ( isset( $business_logo ) && ! empty( $business_logo ) ): ?>
        "logo": "<?php echo $business_logo; ?>",
        "image": "<?php echo $business_logo; ?>",
        <?php endif; ?>
        <?php if($business_locations_count == 'one'): ?>
            <?php if( isset($business_single_locations) && !empty($business_single_locations) ): ?>
                <?php if( !empty( $business_single_locations['meta_latitude'] ) && !empty( $business_single_locations['meta_longitude'] ) ): ?>
        "geo": {
            "@type": "GeoCoordinates",
            "latitude": "<?php echo $business_single_locations['meta_latitude']; ?>",
            "longitude": "<?php echo $business_single_locations['meta_longitude']; ?>"
        },
                <?php endif; ?>
            <?php endif; ?>
            <?php elseif($business_locations_count == 'more'): ?>
            <?php if( isset($business_multiple_locations) && !empty($business_multiple_locations) ): ?>
                <?php $length = count($business_multiple_locations); ?>
                <?php $inc = 0; ?>
        "geo":
        [
                    <?php foreach($business_multiple_locations as $location): ?>
                        <?php if( !empty( $location['meta_latitude'] ) && !empty( $location['meta_longitude'] ) ): ?>
                            <?php if($inc == $length - 1): ?>
            {
                "@type": "GeoCoordinates",
                "latitude": "<?php echo $location['meta_latitude']; ?>",
                "longitude": "<?php echo $location['meta_longitude']; ?>"
            }
                            <?php else: ?>
            {
                "@type": "GeoCoordinates",
                "latitude": "<?php echo $location['meta_latitude']; ?>",
                "longitude": "<?php echo $location['meta_longitude']; ?>"
            },
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php $inc++; ?>
                    <?php endforeach; ?>
        ],
            <?php endif; ?>
        <?php endif; ?>
        "sameAs": [
            <?php echo implode(",\n", $sameAsLinks); ?>
        ],
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "<?php echo $business_rating_info['rating_value']; ?>",
            "bestRating": "<?php echo $business_rating_info['rating_best']; ?>",
            "ratingCount": "<?php echo $business_rating_info['rating_count']; ?>"
        }
    }
    </script>
    <?php
}
add_action('wp_footer', 'output_schema', 9999);

/**
 * ACF Font Awesome grabs it's own silly version of Font Awesome, but we already have one, so it conflicts
 * This causes artefacts to load, and then when their version *finally* loads from their CDN, it causes a FOUC (That's
 * Flash Of Unstyled Content)
 *
 * @param $enqueue
 * @return bool
 */
//function acf__fa_get_url( $enqueue ) {
//    return false;
//}
//add_filter( 'ACFFA_get_fa_url', 'acf__fa_get_url', 10, 1);