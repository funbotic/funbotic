<?php
/**
 * Fired during plugin activation
 *
 * @link       https://www.funbotic.com
 * @since      1.0.1
 *
 * @package    Funbotic
 * @subpackage Funbotic/includes
 * @author     Alexander LaBrie <alabrie@funbotlab.com>
 */

/**
 * Create custom fields when uploading/editing media, to allow a piece of media to be associated with an individual camper.
 */

add_shortcode( 'funbotic_user_gallery', 'funbotic_generate_dynamic_user_gallery' );


function funbotic_generate_dynamic_user_gallery() {
    $id = get_current_user_id();
    $current_meta = get_user_meta( $id, 'funbotic_associated_images', false );
    // Just to be safe, we will clean the array to ensure it is in a single dimension.
    $current_associated_images = funbotic_clean_array( $current_meta );

    (string) $content_string = '[gallery';

    foreach ( $current_associated_images as $image_id ) {
        
    }

    $content_string .= ']';

    echo do_shortcode( $content_string );
}